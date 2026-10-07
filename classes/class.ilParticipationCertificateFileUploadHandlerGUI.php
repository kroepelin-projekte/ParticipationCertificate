<?php

use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Exception\IllegalStateException;
use ILIAS\FileUpload\Handler\AbstractCtrlAwareUploadHandler;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;
use ILIAS\FileUpload\Handler\BasicHandlerResult;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\HandlerResult;
use ILIAS\ResourceStorage\Services;
use ILIAS\ResourceStorage\Stakeholder\ResourceStakeholder;

/**
 * @ilCtrl_isCalledBy ilParticipationCertificateFileUploadHandlerGUI: ilUIPluginRouterGUI, ilRepositoryGUI
 */

class ilParticipationCertificateFileUploadHandlerGUI extends AbstractCtrlAwareUploadHandler
{
    private Services $storage;
    /** @var ResourceStakeholder  */
    private $stakeholder;

    private $logger;

    public function __construct()
    {
        parent::__construct();
        global $DIC;
        $this->storage = $DIC->resourceStorage();
        $this->stakeholder = new Stakeholder();
        $this->logger = $DIC->logger()->root();
    }

    /**
     * @inheritDoc
     */
    public function getUploadURL(): string
    {
        return $this->ctrl->getLinkTargetByClass(
            [ilUIPluginRouterGUI::class, self::class],
            self::CMD_UPLOAD
        );
    }

    /**
     * @return string
     * @throws ilCtrlException
     */
    public function getFileRemovalURL(): string
    {
        return $this->ctrl->getLinkTargetByClass(
            [ilUIPluginRouterGUI::class, self::class],
            self::CMD_REMOVE
        );
    }

    /**
     * @return HandlerResult
     * @throws IllegalStateException
     */
    protected function getUploadResult(): HandlerResult
    {
        $this->upload->process();

        /** @var $result UploadResult */
        $array = $this->upload->getResults();

        $result = end($array);

        if ($result instanceof UploadResult && $result->isOK()) {
            $identifier = $this->storage->manage()
                                        ->upload($result, $this->stakeholder)
                                        ->serialize();
            $status = HandlerResult::STATUS_OK;

            $this->logger->debug('Successful Upload file: '. $identifier);
        } else {
            $identifier = '';
            $status = HandlerResult::STATUS_FAILED;
            $this->logger->debug('Failed Upload file ' . $identifier);
        }

        return new BasicHandlerResult(
            $this->getFileIdentifierParameterName(),
            $status,
            $identifier,
            $result->getStatus()->getMessage()
        );
    }

    /**
     * @param string $identifier
     * @return HandlerResult
     */
    protected function getRemoveResult(string $identifier): HandlerResult
    {
        $id = $this->storage->manage()->find($identifier);

        if ($id !== null) {
            $this->storage->manage()->remove($id, $this->stakeholder);
            $status = HandlerResult::STATUS_OK;
            $message = 'File deleted';
        } else {
            $status = HandlerResult::STATUS_FAILED;
            $message = 'File not found';
        }

        return new BasicHandlerResult(
            $this->getFileIdentifierParameterName(),
            $status,
            $identifier,
            $message
        );
    }

    /**
     * @param string $identifier
     * @return FileInfoResult
     */
    public function getInfoResult(string $identifier) : FileInfoResult
    {
        $id = $this->storage->manage()->find($identifier);

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
    public function getInfoForExistingFiles(array $file_ids) : array
    {
        $infos = [];
        foreach ($file_ids as $file_id) {

            $id = $this->storage->manage()->find($file_id);

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
}