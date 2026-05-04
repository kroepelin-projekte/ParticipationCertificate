<?php

use Twig\Error\SyntaxError;
use Twig\Error\LoaderError;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use Mpdf\MpdfException;
use JetBrains\PhpStorm\NoReturn;

/**
 * Class ilParticipationCertificateTwigParser
 *
 * @ilCtrl_isCalledBy ilParticipationCertificateTwigParser: ilParticipationCertificateGUI, ilParticipationCertificateResultGUI
 */
class ilParticipationCertificateTwigParser
{
    protected ilParticipationCertificatePlugin $pl;

    protected int|array $group_ref_id;

    protected array $usr_ids;

    protected null|int|array $usr_id;

    protected bool $ementor = false;

    protected bool $footer = false;

    protected bool $edited = false;

    protected ?array $array;

    public ilTemplate|ilGlobalTemplateInterface $tpl;

    protected \Twig\TemplateWrapper $twig_template;

    public function __construct(
        ?int $group_ref_id = null,
        array $usr_id = null,
        bool $ementor = true,
        bool $edited = false,
        array|null $array = null,
        bool $selfPrint = false
    ) {
        global $DIC;
        $this->pl = ilParticipationCertificatePlugin::getInstance();
        $this->tpl = $DIC->ui()->mainTemplate();

        if (is_int($group_ref_id)) {
            $this->group_ref_id = $group_ref_id;
            $cert_access = new ilParticipationCertificateAccess($group_ref_id);
            if (!$selfPrint) {
                $this->usr_ids = $cert_access->getUserIdsOfGroup();
            }
        }

        if ($selfPrint) {
            $this->usr_ids = $usr_id;
        }

        $this->usr_id = $usr_id;

        if (empty($this->usr_id)) {
            $this->usr_id = $this->usr_ids;
        }

        $this->ementor = $ementor;
        //wenn die Resultate bearbeitet wurden wird automatisch der footer auf true gesetzt
        if ($edited == true) {
            $this->footer = true;
        }
        $this->edited = $edited;
        //$array sind die abgeänderten werte
        $this->array = $array;

        $loader = new Twig\Loader\FilesystemLoader($this->pl->getDirectory() . '/templates/report/');
        $twig = new Twig\Environment($loader, [
            'cache' => false,
        ]);

        $this->twig_template = $twig->load('certificate.html');
    }

    /**
     * @param array $userIds
     * @return array
     */
    private function excludeUsersFromPrintIfMissingUserData(array $userIds): array
    {
        $arr_usr_data = ilPartCertUsersData::getData($this->pl, $userIds);

        foreach ($userIds as $key => $usrId) {
            $user_data = new ilPartCertUserData();
            if (!$user_data->checkIfUserDataFilled(
                $arr_usr_data[$usrId]->getPartCertSalutation(),
                $arr_usr_data[$usrId]->getPartCertFirstname(),
                $arr_usr_data[$usrId]->getPartCertLastname()
            )) {
                unset($userIds[$key]);
            }
        }
        return array_values($userIds);
    }

    /**
     * @param bool        $selfPrint
     * @param bool        $printIsAsynchronous
     * @param int|null    $courseRefId
     * @param bool|null   $suggestedCourses
     * @param bool|null   $additionalOffer
     * @param bool|null   $initialTest
     * @param bool|null   $homeworkInclude
     * @param bool|null   $individualAssesmentsIncluded
     * @param bool|null   $sessionsIncluded
     * @param string|null $firstname
     * @param string|null $lastname
     * @param string|null $notSugesstedCourses
     * @return void
     * @throws CrossReferenceException
     * @throws LoaderError
     * @throws MpdfException
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws SyntaxError
     * @throws arException
     * @throws ilDatabaseException
     * @throws ilDateTimeException
     * @throws ilObjectNotFoundException
     */
    public function parseData(
        bool $selfPrint = false,
        bool $printIsAsynchronous = false,
        ?int $courseRefId = null,
        ?bool $suggestedCourses = true,
        ?bool $additionalOffer = true,
        ?bool $initialTest = true,
        ?bool $homeworkInclude = true,
        ?bool $individualAssesmentsIncluded = true,
        ?bool $sessionsIncluded = true,
        ?string $firstname = null,
        ?string $lastname = null,
        ?string $notSuggestedCourses = null
    ): void {
        $certConfigs = new ilParticipationCertificateConfigs();
        $objConfig = $certConfigs->getObjConfigSetIfNoneCreateDefaultAndCreateNewObjConfigValues($this->group_ref_id);

        if (count($objConfig) > 0) {
            $globalConfigId = reset($objConfig)->getGlobalConfigId();
        }

        $configText = $this->getConfigTexts($objConfig);

        $this->tpl->setOnScreenMessage('success', $this->pl->txt('print_done'), true);

        $refId = $this->group_ref_id;

        $groupRefId = ParticipationCertificateHelper::getGroupRefId($courseRefId);
        $newIassStates = ilIassStatesMulti::getData($this->usr_ids, (int) $groupRefId);
        $xaliStates = xaliStates::getData($this->usr_ids, $refId);
        $userData = ilPartCertUsersData::getData($this->pl, $this->usr_ids);
        $loMasterCourse = ilLearningObjectivesMasterCrs::getData(ilObject::_lookupObjectId($refId), $this->usr_ids);

        if (!empty($firstname) && !empty($lastname)) {
            $this->setUserData($userData, $firstname, $lastname);
        }

        $initialTestStates = ilCrsInitialTestStates::getData($this->usr_ids);
        $excerciseStates = ilExcerciseStates::getData($this->usr_ids, $this->group_ref_id);
        $learnSuggResults = ilLearnObjectSuggResults::getData($this->usr_ids);

        $partPdf = new ilParticipationCertificatePDFGenerator();

        $logoPath = '';
        $logoIsSavedInResourceStorage = false;
        if (is_numeric($globalConfigId)) {
            $isFile = is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $refId, ilParticipationCertificateConfig::LOGO_FILE_NAME));

            $file = ilParticipationCertificateFiles::getFile(
                $refId,
                'logo'
            );

            if ($isFile && !empty($file) && !$file->getResourceStorage()) {
                $logoPath = ilParticipationCertificateConfig::returnPicturePath('absolute', $refId, ilParticipationCertificateConfig::LOGO_FILE_NAME);
            } else if(!empty($file) && $file->getResourceStorage()) {
                $logoIsSavedInResourceStorage = true;
            }
        } else {
            $file = ilParticipationCertificateFiles::getFile(
                $this->group_ref_id,
                'logo'
            );

            if (!empty($file) && !$file->getResourceStorage() && is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $this->group_ref_id, ilParticipationCertificateConfig::LOGO_FILE_NAME))) {
                $logoPath = ilParticipationCertificateConfig::returnPicturePath('absolute', $this->group_ref_id, ilParticipationCertificateConfig::LOGO_FILE_NAME);
            } elseif (!empty($file) && $file->getResourceStorage()) {
                $logoIsSavedInResourceStorage = true;
            }
        }

        $page1IssuerSignature = '';
        $signatureIsSavedInResourceStorage = false;
        if (is_numeric($globalConfigId)) {
            $isFile = is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $refId, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME));

            $file = ilParticipationCertificateFiles::getFile(
                $refId,
                'page1_issuer_signature'
            );

            if ($isFile && !empty($file) && !$file->getResourceStorage()) {
                $page1IssuerSignature = ilParticipationCertificateConfig::returnPicturePath('absolute', $refId, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME);

            } else if(!empty($file) && $file->getResourceStorage()) {
                $signatureIsSavedInResourceStorage = true;
            }
        } else {
            $file = ilParticipationCertificateFiles::getFile(
                $this->group_ref_id,
                'page1_issuer_signature'
            );

            if (!empty($file) && !$file->getResourceStorage() && is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $this->group_ref_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME))) {
                $page1IssuerSignature = ilParticipationCertificateConfig::returnPicturePath('absolute', $this->group_ref_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME);
            } elseif (!empty($file) && $file->getResourceStorage()) {
                $signatureIsSavedInResourceStorage = true;
            }
        }

        if (!$selfPrint) {
            $this->usr_id = $this->excludeUsersFromPrintIfMissingUserData($this->usr_id);
        }

        foreach ($this->usr_id as $usr_id) {
            $countIndividualAssessments = 0;
            $countCompletedIndividualAssessments = 0;

            if (!empty($newIassStates[$usr_id])) {
                $individualAssessmentsUser = $newIassStates[$usr_id];
                $countIndividualAssessments = count((array) $individualAssessmentsUser);
                $countCompletedIndividualAssessments = $this->getCompletedIndividualAssessments((array) $individualAssessmentsUser);
            }

            $sessions = ParticipationCertificateHelper::getSessions((int) $groupRefId);
            $countSessions = count($sessions);
            $countAttendedSessions = 0;
            foreach ($sessions as $session) {
                $eventParticipants = new ilEventParticipants($session['obj_id']);
                if($eventParticipants->hasParticipated($usr_id)) {
                    $countAttendedSessions++;
                }
            }

            $arr_render = $this->fetchDataCertificate(
                $usr_id,
                $refId,
                $this->ementor,
                $configText,
                $userData,
                $loMasterCourse,
                $initialTestStates,
                $learnSuggResults,
                $excerciseStates,
                $newIassStates,
                $xaliStates,
                $logoIsSavedInResourceStorage,
                $signatureIsSavedInResourceStorage,
                $selfPrint,
                $certConfigs->returnPercentValue($this->group_ref_id),
                $logoPath,
                $page1IssuerSignature,
                $suggestedCourses,
                $additionalOffer,
                $initialTest,
                $homeworkInclude,
                $individualAssesmentsIncluded,
                $sessionsIncluded,
                $firstname,
                $lastname,
                $countIndividualAssessments,
                $countCompletedIndividualAssessments,
                $countSessions,
                $countAttendedSessions,
                $notSuggestedCourses
            );

            $partPdf->generatePDF(
                $this->twig_template->render($arr_render),
                count($this->usr_id),
                $printIsAsynchronous
            );
        }
    }

    /**
     * @param array     $coursesToPrint
     * @param string    $firstname
     * @param string    $lastname
     * @param int       $userId
     * @param bool      $printIsAsynchronous
     * @param bool|null $homeworkInclude
     * @param bool|null $individualAssesmentsIncluded
     * @param bool|null $sessionsIncluded
     * @return void
     * @throws CrossReferenceException
     * @throws LoaderError
     * @throws MpdfException
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws SyntaxError
     * @throws arException
     * @throws ilDateTimeException
     */
    public function parseDataMultipleCourses(
        array $coursesToPrint,
        string $firstname,
        string $lastname,
        int $userId,
        bool $printIsAsynchronous = false,
        ?bool $homeworkInclude = true,
        ?bool $individualAssesmentsIncluded = true,
        ?bool $sessionsIncluded = true
    ): void {
        global $DIC;

        $tree = $DIC->repositoryTree();
        $part_pdf = new ilParticipationCertificatePDFGenerator();
        foreach ( $coursesToPrint as $courseObj ) {
            $containerRefId = $tree->getParentId($courseObj['ref_id']);

            $countIndividualAssessments = 0;
            $countCompletedIndividualAssessments = 0;
            $countSessions = 0;
            $countAttendedSessions = 0;
            if ($containerRefId !== null) {
                $newIassStates = ilIassStatesMulti::getData($this->usr_ids, (int) $courseObj['group_id']);

                $individualAssessmentsUser = [];
                if (!empty($newIassStates)) {
                    $individualAssessmentsUser = $newIassStates[$userId];
                }

                $countIndividualAssessments = count(array_keys((array) $individualAssessmentsUser));
                $countCompletedIndividualAssessments = $this->getCompletedIndividualAssessments((array) $individualAssessmentsUser);

                $sessions = ParticipationCertificateHelper::getSessions((int) $courseObj['group_id']);

                $countSessions = 0;
                if (!empty($sessions)) {
                    $countSessions = count(array_keys($sessions));
                }

                foreach ($sessions as $session) {
                    $eventParticipants = new ilEventParticipants($session['obj_id']);
                    if($eventParticipants->hasParticipated($userId)) {
                        $countAttendedSessions++;
                    }
                }
            }

            $certConfigs = new ilParticipationCertificateConfigs();
            $objConfig = $certConfigs->getObjConfigSetIfNoneCreateDefaultAndCreateNewObjConfigValues($courseObj['ref_id']);

            if (count($objConfig) > 0) {
                $globalConfigId = reset($objConfig)->getGlobalConfigId();
            }

            $configTexts = $this->getConfigTexts($objConfig);

            $this->tpl->setOnScreenMessage('success', $this->pl->txt('print_done'), true);

            $newIassStates = ilIassStatesMulti::getData($this->usr_ids, $courseObj['ref_id']);
            $xaliStates = xaliStates::getData($this->usr_ids, $courseObj['ref_id']);

            $userData = ilPartCertUsersData::getData($this->pl, $this->usr_ids);
            $loMasterCourse = ilLearningObjectivesMasterCrs::getData(ilObject::_lookupObjectId($courseObj['ref_id']), $this->usr_ids);

            $this->setUserData($userData, $firstname, $lastname);

            $initialTestStates = ilCrsInitialTestStates::getData($this->usr_ids);
            $excerciseStates = ilExcerciseStates::getData($this->usr_ids, $courseObj['ref_id']);
            $learnSugestionResults = ilLearnObjectSuggResults::getData($this->usr_ids);

            $logoPath = '';
            $logoIsSavedInResourceStorage = false;
            if (is_numeric($globalConfigId)) {
                $isFile = is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $courseObj['ref_id'], ilParticipationCertificateConfig::LOGO_FILE_NAME));

                $file = ilParticipationCertificateFiles::getFile(
                    $courseObj['ref_id'],
                    'logo'
                );

                if ($isFile && !empty($file) && !$file->getResourceStorage()) {
                    $logoPath = ilParticipationCertificateConfig::returnPicturePath('absolute', $courseObj['ref_id'], ilParticipationCertificateConfig::LOGO_FILE_NAME);
                } else if(!empty($file) && $file->getResourceStorage()) {
                    $logoIsSavedInResourceStorage = true;
                }
            } else {
                $file = ilParticipationCertificateFiles::getFile(
                    $courseObj['ref_id'],
                    'logo'
                );

                if (!empty($file) && !$file->getResourceStorage() && is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $courseObj['ref_id'], ilParticipationCertificateConfig::LOGO_FILE_NAME))) {
                    $logoPath = ilParticipationCertificateConfig::returnPicturePath('absolute', $courseObj['ref_id'], ilParticipationCertificateConfig::LOGO_FILE_NAME);
                } elseif (!empty($file) && $file->getResourceStorage()) {
                    $logoIsSavedInResourceStorage = true;
                }
            }

            $page1IssuerSignature = '';
            $signatureIsSavedInResourceStorage = false;
            if (is_numeric($globalConfigId)) {
                $isFile = is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $courseObj['ref_id'], ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME));

                $file = ilParticipationCertificateFiles::getFile(
                    $courseObj['ref_id'],
                    'page1_issuer_signature'
                );

                if ($isFile && !empty($file) && !$file->getResourceStorage()) {
                    $page1IssuerSignature = ilParticipationCertificateConfig::returnPicturePath('absolute', $courseObj['ref_id'], ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME);

                } else if(!empty($file) && $file->getResourceStorage()) {
                    $signatureIsSavedInResourceStorage = true;
                }
            } else {
                $file = ilParticipationCertificateFiles::getFile(
                    $courseObj['ref_id'],
                    'page1_issuer_signature'
                );

                if (!empty($file) && !$file->getResourceStorage() && is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $courseObj['ref_id'], ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME))) {
                    $page1IssuerSignature = ilParticipationCertificateConfig::returnPicturePath('absolute', $courseObj['ref_id'], ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME);
                } elseif (!empty($file) && $file->getResourceStorage()) {
                    $signatureIsSavedInResourceStorage = true;
                }
            }

            $notSuggestedCourses = $courseObj['not_suggested_courses'] ?? null;

            $arr_render = $this->fetchDataCertificate(
                $this->usr_id[0],
                $courseObj['ref_id'],
                $this->ementor,
                $configTexts,
                $userData,
                $loMasterCourse,
                $initialTestStates,
                $learnSugestionResults,
                $excerciseStates,
                $newIassStates,
                $xaliStates,
                $logoIsSavedInResourceStorage,
                $signatureIsSavedInResourceStorage,
                true,
                $certConfigs->returnPercentValue($courseObj['ref_id']),
                $logoPath,
                $page1IssuerSignature,
                $courseObj['suggested_courses'] ?? false,
                $courseObj['additional_offer'] ?? false,
                $courseObj['entry_test'] ?? false,
                $homeworkInclude,
                $individualAssesmentsIncluded,
                $sessionsIncluded,
                $firstname,
                $lastname,
                $countIndividualAssessments,
                $countCompletedIndividualAssessments,
                $countSessions,
                $countAttendedSessions,
                $notSuggestedCourses
            );
            $part_pdf->generatePDF(
                $this->twig_template->render($arr_render),
                count($coursesToPrint),
                $printIsAsynchronous
            );
        }
    }

    /**
     * @param array $individualAssessmentsUser
     * @return int
     */
    private function getCompletedIndividualAssessments(array $individualAssessmentsUser): int
    {
        $count = 0;
        foreach ($individualAssessmentsUser as $assessmentUser) {
            if ($assessmentUser->getPassed() === 1) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @param $objConfig
     * @return array
     */
    private function getConfigTexts($objConfig): array
    {
        $configTexts = [];
        foreach ($objConfig as $config) {
            $configTexts[$config->getConfigKey()] = $config->getConfigValue();
        }
        return $configTexts;
    }

    /**
     * @param array  $userData
     * @param string $firstname
     * @param string $lastname
     * @return void
     */
    private function setUserData(
        array $userData,
        string $firstname,
        string $lastname
    ): void {
        foreach ($userData as $data) {
            if(empty($data->getPartCertFirstname())) {
                $data->setPartCertFirstname($firstname);
            }

            if(empty($data->getPartCertLastname())) {
                $data->setPartCertLastname($lastname);
            }
        }
    }

    /**
     * @param int         $userId
     * @param int         $refId
     * @param bool        $eMentoring
     * @param array       $configTexts
     * @param array       $userData
     * @param array       $loMasterCourse
     * @param array       $initialTestStates
     * @param array       $learnSuggestionResults
     * @param array       $excerciseStates
     * @param array       $newIassStates
     * @param array       $xaliStates
     * @param bool        $logoIsSavedInResourceStorage
     * @param bool        $signatureIsSavedInResourceStorage
     * @param bool        $selfPrint
     * @param string      $certConfigValue
     * @param string      $logoPath
     * @param string      $page1IssuerSignature
     * @param bool        $suggestedCourses
     * @param bool        $additionalOffer
     * @param bool        $initialTest
     * @param bool        $homeworkIncluded
     * @param bool|null   $individualAssesmentsIncluded
     * @param bool|null   $sessionsIncluded
     * @param string|null $firstname
     * @param string|null $lastname
     * @param int|null    $countIndividualAssessments
     * @param int|null    $countCompletedIndividualAssessments
     * @param int|null    $countSessions
     * @param int|null    $countAttendedSessions
     * @param string|null $notSuggestedCourses
     * @return array
     * @throws LoaderError
     * @throws SyntaxError
     * @throws ilDateTimeException
     */
    #[NoReturn]
    private function fetchDataCertificate(
        int $userId,
        int $refId,
        bool $eMentoring,
        array $configTexts,
        array $userData,
        array $loMasterCourse,
        array $initialTestStates,
        array $learnSuggestionResults,
        array $excerciseStates,
        array $newIassStates,
        array $xaliStates,
        bool $logoIsSavedInResourceStorage,
        bool $signatureIsSavedInResourceStorage,
        bool $selfPrint,
        string $certConfigValue,
        string $logoPath,
        string $page1IssuerSignature,
        bool $suggestedCourses,
        bool $additionalOffer,
        bool $initialTest,
        ?bool $homeworkIncluded = true,
        ?bool $individualAssesmentsIncluded = true,
        ?bool $sessionsIncluded = true,
        ?string $firstname = null,
        ?string $lastname = null,
        ?int $countIndividualAssessments = null,
        ?int $countCompletedIndividualAssessments = null,
        ?int $countSessions = null,
        ?int $countAttendedSessions = null,
        ?string $notSuggestedCourses = null
    ): array {
        $date = new ilDate(time(), IL_CAL_UNIX);
        $percentage = 0;

        //quickfix, wenn man user auswählt kann es sein, das $usr_id ein array bleibt. Das führt weiter unten zum crash. So wird das array aufgelöst.
        if (is_array($userId)) {
            $userId = $userId[0];
        }
        $processedTextValues = $configTexts;

        //Preprocess text values
        foreach ($configTexts as $key => $value) {
            $twig = new Twig\Environment(new Twig\Loader\ArrayLoader());

            // Twig use the placeholders {{ }}, but plugins the  [[ ]]
            $value = $this->preparePlaceholdersForTwig($value);

            $template = $twig->createTemplate((string) $value);

            $peparsedValue = $template->render([
                'username' => ($userData[$userId]->getPartCertSalutation() ?
                        $userData[$userId]->getPartCertSalutation() . ' ' : '') .
                    ($firstname ?? $userData[$userId]->getPartCertFirstname()) . ' ' .
                    ($lastname ?? $userData[$userId]->getPartCertLastname()),
                'date' => $date->get(IL_CAL_FKT_DATE, 'd.m.Y')
            ]);

            $processedTextValues[$key] = $peparsedValue;
        }

        //Learning Objective Master Course
        $useLoMasterCourse = array();
        if (is_array($loMasterCourse) && array_key_exists($userId, $loMasterCourse) && is_array($loMasterCourse[$userId])) {
            $useLoMasterCourse = $loMasterCourse[$userId];
        }
        if ($this->edited) {
            $initialTestState = $this->array[0];
            $learnSuggestResults = $this->array[1];
            $iassState = $this->array[2];
            $excercisePercentage = $this->array[3];
        } else {

            //Initial Test
            $initialTestState = 0;
            if (key_exists($userId, $initialTestStates) && is_object($initialTestStates[$userId])) {
                $initialTestState = $initialTestStates[$userId]->getCrsitestItestSubmitted();
            }
            //Percentage final tests of suggested modules
            $learnSuggestResults = 0;
            if (key_exists($userId, $learnSuggestionResults) && is_object($learnSuggestionResults[$userId])) {
                $learnSuggestResults = $learnSuggestionResults[$userId]->getAveragePercentage(ilParticipationCertificateConfig::getConfig('calculation_type_processing_state_suggested_objectives', $refId), true);
            }
            //Home Work
            $excercisePercentage = 0;
            if (key_exists($userId, $excerciseStates) && is_object($excerciseStates[$userId])) {
                $excercisePercentage = $excerciseStates[$userId]->getPassedPercentage();
            }
        }

        /*Video Conferences */
        $countPassed = 0;
        $countTests = 0;
        if (key_exists($userId, $newIassStates) && is_array($newIassStates[$userId])) {
            foreach ($newIassStates[$userId] as $item) {
                $countPassed = $countPassed + $item->getPassed();
                $countTests = $countTests + $item->getTotal();
            }
        }

        if (key_exists($userId, $xaliStates) && is_object($xaliStates[$userId])) {
            $countPassed = $countPassed + $xaliStates[$userId]->getPassed();
            $countTests = $countTests + $xaliStates[$userId]->getTotal();
        }

        if ($countTests > 0) {
            $percentage = $countPassed / $countTests * 100;

            switch ($countTests) {
                case 1:
                    if ($countPassed == 1) {
                        $iassStates = "<img alt='' src=" . ILIAS_ABSOLUTE_PATH . "/" . $this->pl->getImagePath("passed_s.png") . ">";
                    } else {
                        $iassStates = "<img alt='' src=" . ILIAS_ABSOLUTE_PATH . "/" . $this->pl->getImagePath("failed_s.png") . ">";
                    }
                    break;
                default:
                    $iassStates = $countPassed . "/" . $countTests;
                    break;
            }
        } else {
            $iassStates = "<img alt='' src=" . ILIAS_ABSOLUTE_PATH . "/" . $this->pl->getImagePath("not_attempted_s.png") . ">";
        }

        if ($logoIsSavedInResourceStorage) {
            if(!empty($processedTextValues['logo'])) {
                $file = new ilParticipationCertificateFiles();
                $src = $file->getFileSrcByStorageType(
                    $processedTextValues['logo'],
                    $refId,
                    'logo'
                );

                $logoPath = $src;
            }

        }

        if ($signatureIsSavedInResourceStorage && !empty($processedTextValues['page1_issuer_signature'])) {
            $file = new ilParticipationCertificateFiles();
            $src = $file->getFileSrcByStorageType(
                $processedTextValues['page1_issuer_signature'],
                $refId,
                'page1_issuer_signature'
            );

            $page1IssuerSignature = $src;
        }

        if (!$selfPrint) {
            $suggestedCourses = true;
            $additionalOffer = true;
            $initialTest = true;
        }

        if ($eMentoring) {
            $eMentoring = (bool) ilParticipationCertificateConfig::getConfig('enable_ementoring', $refId);
        }

        $data = [
            'text_values' => $processedTextValues,
            'show_ementoring' => $eMentoring,
            'show_footer' => $this->footer,
            'arr_lo_master_crs' => $useLoMasterCourse,
            'crsitest_itest_submitted' => $initialTestState,
            'learn_sugg_reached_percentage' => $learnSuggestResults,
            'iass_state' => $percentage,
            'iass_states' => $iassStates,
            'excercise_percentage' => $excercisePercentage,
            'logo_path' => $logoPath,
            'page1_issuer_signature' => $page1IssuerSignature,
            'standard_value' => $certConfigValue,
            'individual_assessments_heading' => $this->pl->txt('individual_assessments_heading'),
            'individual_assessments' => [
                'label' => $this->pl->txt('individual_assessments'),
                'value' => $countCompletedIndividualAssessments . '/' . $countIndividualAssessments
            ],
            'sessions' => [
                'label' => $this->pl->txt('sessions'),
                'value' => $countAttendedSessions . '/' . $countSessions
            ],
            'homework_included' => $homeworkIncluded,
            'individual_assesments_included' => $individualAssesmentsIncluded,
            'sessions_included' => $sessionsIncluded,
            'suggested_courses' => $suggestedCourses,
            'additional_offer' => $additionalOffer,
            'initial_test' => $initialTest,
            'not_suggested_courses' => [
                'label' => $this->pl->txt('not_suggested_courses'),
                'value' => $notSuggestedCourses
            ]
        ];

        return $data;
    }

    /**
     * Replace the placeholders [[ ]] with the {{ }}
     *
     * @param string $value
     * @return array|string|string[]
     */
    private function preparePlaceholdersForTwig(string $value)
    {
        $value = str_replace('[[', '{{', $value);
        $value = str_replace(']]', '}}', $value);

        return $value;
    }
}
