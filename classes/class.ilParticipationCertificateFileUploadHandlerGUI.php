<?php

use ILIAS\Filesystem\Stream\Streams;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Exception\IllegalStateException;
use ILIAS\FileUpload\Handler\AbstractCtrlAwareUploadHandler;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;
use ILIAS\FileUpload\Handler\BasicHandlerResult;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\HandlerResult;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\ResourceStorage\Services;
use ILIAS\ResourceStorage\Stakeholder\ResourceStakeholder;

/**
 * Upload handler for the certificate logo and the issuer signature.
 *
 * The handler is bound to a ref_id (course/group, or the component settings
 * node in the plugin administration). Every request requires write permission
 * on that ref_id. Only PNG/JPEG images are accepted, and remove/info only
 * work on resources owned by this plugin's stakeholder.
 *
 * @ilCtrl_isCalledBy ilParticipationCertificateFileUploadHandlerGUI: ilUIPluginRouterGUI, ilRepositoryGUI
 */
class ilParticipationCertificateFileUploadHandlerGUI extends AbstractCtrlAwareUploadHandler
{
    private const int MAX_FILE_SIZE = 2 * 1024 * 1024;

    private const array ALLOWED_IMAGE_TYPES = [
        IMAGETYPE_PNG,
        IMAGETYPE_JPEG,
    ];

    /** Config keys whose values are resource identifications of stored images. */
    private const array FILE_CONFIG_KEYS = [
        'logo',
        'page1_issuer_signature',
    ];

    private Services $storage;

    private ResourceStakeholder $stakeholder;

    private ilLogger $logger;

    private ilParticipationCertificatePlugin $pl;

    private ilLanguage $lng;

    private ?int $ref_id;

    public function __construct(?int $ref_id = null)
    {
        parent::__construct();
        global $DIC;
        $this->storage = $DIC->resourceStorage();
        $this->stakeholder = new Stakeholder();
        $this->logger = $DIC->logger()->root();
        $this->pl = ilParticipationCertificatePlugin::getInstance();
        $this->lng = $DIC->language();
        $this->ref_id = $ref_id ?? Helper::fetchUrlParameter('ref_id', 'int');
    }

    /**
     * Rejects every command if the current user has no write permission on the bound ref_id.
     */
    public function executeCommand(): void
    {
        if (!$this->hasCurrentUserAccess()) {
            $this->logger->warning(
                'Participation certificate upload handler: access denied for cmd "' . $this->ctrl->getCmd() . '"'
            );
            $this->sendJson(new BasicHandlerResult(
                $this->getFileIdentifierParameterName(),
                HandlerResult::STATUS_FAILED,
                '',
                $this->lng->txt('no_permission')
            ));
            return;
        }

        parent::executeCommand();
    }

    /**
     * @inheritDoc
     */
    public function getUploadURL(): string
    {
        return $this->buildLink(self::CMD_UPLOAD);
    }

    /**
     * @return string
     * @throws ilCtrlException
     */
    public function getFileRemovalURL(): string
    {
        return $this->buildLink(self::CMD_REMOVE);
    }

    /**
     * @return string
     * @throws ilCtrlException
     */
    public function getExistingFileInfoURL(): string
    {
        return $this->buildLink(self::CMD_INFO);
    }

    /**
     * @return HandlerResult
     * @throws IllegalStateException
     */
    protected function getUploadResult(): HandlerResult
    {
        $this->upload->process();

        $array = $this->upload->getResults();
        $result = end($array);

        if (!$result instanceof UploadResult) {
            return $this->failedResult('', $this->pl->txt('failure_save_config'));
        }

        if (!$result->isOK()) {
            return $this->failedResult('', $result->getStatus()->getMessage());
        }

        $validation_error = $this->validateImage($result);
        if ($validation_error !== null) {
            $this->logger->debug('Rejected upload "' . $result->getName() . '": ' . $validation_error);
            return $this->failedResult('', $validation_error);
        }

        $identifier = $this->storage->manage()
                                    ->upload($result, $this->stakeholder)
                                    ->serialize();

        $this->logger->debug('Successful Upload file: ' . $identifier);

        return new BasicHandlerResult(
            $this->getFileIdentifierParameterName(),
            HandlerResult::STATUS_OK,
            $identifier,
            $result->getStatus()->getMessage()
        );
    }

    /**
     * Only removes plugin-owned resources that are not yet referenced by a saved
     * configuration, i.e. files uploaded in the current form that have not been saved.
     */
    protected function getRemoveResult(string $identifier): HandlerResult
    {
        $id = $this->findOwnResource($identifier);

        if ($id === null) {
            return $this->failedResult($identifier, 'File not found');
        }

        if ($this->isReferencedByConfig($identifier)) {
            $this->logger->warning('Refused to remove resource referenced by a configuration: ' . $identifier);
            return $this->failedResult($identifier, $this->lng->txt('no_permission'));
        }

        $this->storage->manage()->remove($id, $this->stakeholder);

        return new BasicHandlerResult(
            $this->getFileIdentifierParameterName(),
            HandlerResult::STATUS_OK,
            $identifier,
            'File deleted'
        );
    }

    /**
     * @param string $identifier
     * @return FileInfoResult
     */
    public function getInfoResult(string $identifier): FileInfoResult
    {
        $id = $this->findOwnResource($identifier);

        if ($id === null) {
            return new BasicFileInfoResult($this->getFileIdentifierParameterName(), 'unknown', 'unknown', 0, 'unknown');
        }

        $result = $this->storage->manage()->getCurrentRevision($id)->getInformation();

        return new BasicFileInfoResult(
            $this->getFileIdentifierParameterName(),
            $identifier,
            $result->getTitle(),
            $result->getSize(),
            $result->getMimeType()
        );
    }

    /**
     * @param array $file_ids
     * @return array
     */
    public function getInfoForExistingFiles(array $file_ids): array
    {
        $infos = [];
        foreach ($file_ids as $file_id) {
            $id = $this->findOwnResource((string) $file_id);

            if ($id === null) {
                continue;
            }

            $result = $this->storage->manage()->getCurrentRevision($id)->getInformation();

            $infos[] = new BasicFileInfoResult(
                $this->getFileIdentifierParameterName(),
                $file_id,
                $result->getTitle(),
                $result->getSize(),
                $result->getMimeType()
            );
        }

        return $infos;
    }

    /**
     * Write permission on the bound ref_id: course/group admins in the repository,
     * plugin administrators on the component settings node.
     */
    private function hasCurrentUserAccess(): bool
    {
        if ($this->ref_id === null || $this->ref_id <= 0) {
            return false;
        }

        return (new ilParticipationCertificateAccess($this->ref_id))->hasCurrentUserAdminAccess();
    }

    /**
     * Server-side check; the dropzone's accepted mime types and max size are client-side only.
     * The mime type of the UploadResult is the one sent by the client, so the content is checked.
     */
    private function validateImage(UploadResult $result): ?string
    {
        if ($result->getSize() <= 0 || $result->getSize() > self::MAX_FILE_SIZE) {
            return $this->pl->txt('upload_file_too_large');
        }

        $image_info = @getimagesize($result->getPath());
        if ($image_info === false || !in_array($image_info[2], self::ALLOWED_IMAGE_TYPES, true)) {
            return $this->pl->txt('upload_invalid_file_type');
        }

        $extension = strtolower(pathinfo($result->getName(), PATHINFO_EXTENSION));
        if (!in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
            return $this->pl->txt('upload_invalid_file_type');
        }

        return null;
    }

    /**
     * Returns the resource only if it exists and is owned by this plugin's stakeholder.
     */
    private function findOwnResource(string $identifier): ?ResourceIdentification
    {
        return ilParticipationCertificateFiles::findOwnResource($identifier);
    }

    private function isReferencedByConfig(string $identifier): bool
    {
        return ilParticipationCertificateConfig::where([
            'config_key' => self::FILE_CONFIG_KEYS,
            'config_value' => $identifier,
        ])->hasSets();
    }

    private function failedResult(string $identifier, string $message): HandlerResult
    {
        return new BasicHandlerResult(
            $this->getFileIdentifierParameterName(),
            HandlerResult::STATUS_FAILED,
            $identifier,
            $message
        );
    }

    /**
     * @throws ilCtrlException
     */
    private function buildLink(string $cmd): string
    {
        if ($this->ref_id !== null) {
            $this->ctrl->setParameterByClass(self::class, 'ref_id', $this->ref_id);
        }

        return $this->ctrl->getLinkTargetByClass(
            [ilUIPluginRouterGUI::class, self::class],
            $cmd
        );
    }

    private function sendJson(HandlerResult $result): void
    {
        $response = $this->http->response()->withBody(Streams::ofString(json_encode($result)));
        $this->http->saveResponse($response);
        $this->http->sendResponse();
        $this->http->close();
    }
}
