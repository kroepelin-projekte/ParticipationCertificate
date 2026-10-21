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

    /**
     * @param int|null   $group_ref_id
     * @param array|null $usr_id
     * @param bool       $ementor
     * @param bool       $edited
     * @param array|null $array
     * @param bool       $self_print
     * @throws LoaderError
     * @throws SyntaxError
     * @throws \Twig\Error\RuntimeError
     */
    public function __construct(
        ?int $group_ref_id = null,
        array $usr_id = null,
        bool $ementor = true,
        bool $edited = false,
        array|null $array = null,
        bool $self_print = false
    ) {
        global $DIC;

        $this->pl = ilParticipationCertificatePlugin::getInstance();
        $this->tpl = $DIC->ui()->mainTemplate();

        if (is_int($group_ref_id)) {
            $this->group_ref_id = $group_ref_id;
            $cert_access = new ilParticipationCertificateAccess($group_ref_id);
            if (!$self_print) {
                $this->usr_ids = $cert_access->getUserIdsOfGroup();
            }
        }

        if ($self_print) {
            $this->usr_ids = $usr_id;
        }

        $this->usr_id = $usr_id;

        if (empty($this->usr_id)) {
            $this->usr_id = $this->usr_ids;
        }

        $this->ementor = $ementor;
        //wenn die Resultate bearbeitet wurden wird automatisch der footer auf true gesetzt
        if ($edited) {
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
     * @param array $user_ids
     * @return array
     */
    private function excludeUsersFromPrintIfMissingUserData(array $user_ids): array
    {
        $arr_usr_data = ilPartCertUsersData::getData($this->pl, $user_ids);

        foreach ($user_ids as $key => $usrId) {
            $user_data = new ilPartCertUserData();

            if (!$user_data->checkIfUserDataFilled(
                $arr_usr_data[$usrId]->getPartCertSalutation(),
                $arr_usr_data[$usrId]->getPartCertFirstname(),
                $arr_usr_data[$usrId]->getPartCertLastname()
            )) {
                unset($user_ids[$key]);
            }
        }
        return array_values($user_ids);
    }

    /**
     * @param int $ref_id
     * @param int $test_active_id
     * @param int $test_tries
     * @return float
     */
    public function getFinalTestReachedPointsInPercent(
        int $ref_id,
        int $test_active_id,
        int $test_tries
    ): float {
        $obj_id = ilObject::_lookupObjId($ref_id);

        $test_ref_id = ilLOSettings::getInstanceByObjId($obj_id)->getTestByType(ilLOSettings::TYPE_TEST_QUALIFIED);
        $test_object = new ilObjTest($test_ref_id, true);
        $test_data = $test_object->getCompleteEvaluationData();
        $participant_data = $test_data->getParticipant($test_active_id);
        $attempt_data = $participant_data?->getPass($test_tries - 1);

        $reached_points = $attempt_data->getReachedPoints();
        $max_points = $attempt_data->getMaxPoints();

        if ($reached_points === 0.0 || $max_points === 0.0) {
            return 0.0;
        }
        return $reached_points / $max_points * 100;
    }

    /**
     * @param bool        $self_print
     * @param bool        $print_is_asynchronous
     * @param int|null    $course_ref_id
     * @param bool|null   $suggested_courses
     * @param bool|null   $additional_offer
     * @param bool|null   $final_test
     * @param bool|null   $homework_include
     * @param bool|null   $individualAssesmentsIncluded
     * @param bool|null   $sessions_included
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
    #[NoReturn]
    public function parseData(
        bool $self_print = false,
        bool $print_is_asynchronous = false,
        ?int $course_ref_id = null,
        ?bool $suggested_courses = true,
        ?bool $additional_offer = true,
        ?bool $final_test = true,
        ?bool $homework_include = true,
        ?bool $sessions_included = true,
        ?string $firstname = null,
        ?string $lastname = null
    ): void {
        $cert_configs = new ilParticipationCertificateConfigs();
        $obj_config = $cert_configs->getObjConfigSetIfNoneCreateDefaultAndCreateNewObjConfigValues($this->group_ref_id);

        if (count($obj_config) > 0) {
            $global_config_id = reset($obj_config)->getGlobalConfigId();
        }

        $config_text = $this->getConfigTexts($obj_config);

        $this->tpl->setOnScreenMessage('success', $this->pl->txt('print_done'), true);

        $ref_id = $this->group_ref_id;
        $course_obj_id = ilObject::_lookupObjectId($ref_id);
        $group_ref_id = ParticipationCertificateHelper::getGroupRefId($course_ref_id);
        $new_iass_states = ilIassStatesMulti::getData($this->usr_ids, (int) $group_ref_id);
        $user_data = ilPartCertUsersData::getData($this->pl, $this->usr_ids);
        $lo_master_course = ilLearningObjectivesMasterCrs::getData($course_obj_id, $this->usr_ids);

        if (!empty($firstname) && !empty($lastname)) {
            $this->setUserData($user_data, $firstname, $lastname);
        }

        $initial_test_states = ilCrsInitialTestStates::getData($this->usr_ids);
        $final_test_states = ilCrsFinalTestStates::getData($this->usr_ids, (int) $ref_id);
        $excercise_states = ilExcerciseStates::getData($this->usr_ids, $this->group_ref_id);
        $learn_sugg_results = ilLearnObjectSuggResults::getData($course_obj_id, $this->usr_ids);
        $part_pdf = new ilParticipationCertificatePDFGenerator();

        $logo_path = '';
        $logo_is_saved_in_resource_storage = false;
        if (is_numeric($global_config_id)) {
            $is_file = is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $ref_id, ilParticipationCertificateConfig::LOGO_FILE_NAME));

            $file = ilParticipationCertificateFiles::getFile(
                $ref_id,
                'logo'
            );

            if ($is_file && !empty($file) && !$file->getResourceStorage()) {
                $logo_path = ilParticipationCertificateConfig::returnPicturePath('absolute', $ref_id, ilParticipationCertificateConfig::LOGO_FILE_NAME);
            } else if(!empty($file) && $file->getResourceStorage()) {
                $logo_is_saved_in_resource_storage = true;
            }
        } else {
            $file = ilParticipationCertificateFiles::getFile(
                $this->group_ref_id,
                'logo'
            );

            if (!empty($file) && !$file->getResourceStorage() && is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $this->group_ref_id, ilParticipationCertificateConfig::LOGO_FILE_NAME))) {
                $logo_path = ilParticipationCertificateConfig::returnPicturePath('absolute', $this->group_ref_id, ilParticipationCertificateConfig::LOGO_FILE_NAME);
            } elseif (!empty($file) && $file->getResourceStorage()) {
                $logo_is_saved_in_resource_storage = true;
            }
        }

        $page1_issuer_signature = '';
        $signature_is_saved_in_resource_storage = false;
        if (is_numeric($global_config_id)) {
            $is_file = is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $ref_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME));

            $file = ilParticipationCertificateFiles::getFile(
                $ref_id,
                'page1_issuer_signature'
            );

            if ($is_file && !empty($file) && !$file->getResourceStorage()) {
                $page1_issuer_signature = ilParticipationCertificateConfig::returnPicturePath('absolute', $ref_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME);

            } else if(!empty($file) && $file->getResourceStorage()) {
                $signature_is_saved_in_resource_storage = true;
            }
        } else {
            $file = ilParticipationCertificateFiles::getFile(
                $this->group_ref_id,
                'page1_issuer_signature'
            );

            if (!empty($file) && !$file->getResourceStorage() && is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $this->group_ref_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME))) {
                $page1_issuer_signature = ilParticipationCertificateConfig::returnPicturePath('absolute', $this->group_ref_id, ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME);
            } elseif (!empty($file) && $file->getResourceStorage()) {
                $signature_is_saved_in_resource_storage = true;
            }
        }

        if (!$self_print) {
            $this->usr_id = $this->excludeUsersFromPrintIfMissingUserData($this->usr_id);
        }

        $rendered_certificates = [];
        foreach ($this->usr_id as $usr_id) {
            $count_individual_assessments = 0;
            $count_completed_individual_assessments = 0;

            if (!empty($new_iass_states[$usr_id])) {
                $individual_assessments_user = $new_iass_states[$usr_id];
                $count_individual_assessments = count((array) $individual_assessments_user);
                $count_completed_individual_assessments = $this->getCompletedIndividualAssessments((array) $individual_assessments_user);
            }

            $sessions = ParticipationCertificateHelper::getSessions((int) $group_ref_id);
            $count_sessions = count($sessions);
            $count_attended_sessions = 0;
            foreach ($sessions as $session) {
                $event_participants = new ilEventParticipants($session['obj_id']);
                if($event_participants->hasParticipated($usr_id)) {
                    $count_attended_sessions++;
                }
            }

            $not_suggested_courses = TrackingTool::getNotRecommendedLearningObjectivesForCertificate(
                (int) $usr_id,
                $course_ref_id
            );

            $final_test_reached_points_percent = 0;
            if (
                !empty($final_test_states[(int) $usr_id]['test_active_id']) &&
                !empty($final_test_states[(int) $usr_id]['crsqtest_qtest_tries'])
            ) {
                $final_test_reached_points_percent = round($this->getFinalTestReachedPointsInPercent(
                    $ref_id,
                    $final_test_states[ (int) $usr_id]['test_active_id'],
                    $final_test_states[ (int) $usr_id]['crsqtest_qtest_tries']
                ), 0);
            }

            $arr_render = $this->fetchDataCertificate(
                $usr_id,
                $ref_id,
                $this->ementor,
                $config_text,
                $user_data,
                $lo_master_course,
                $initial_test_states,
                $final_test_states,
                $final_test_reached_points_percent,
                $learn_sugg_results,
                $excercise_states,
                $new_iass_states,
                $logo_is_saved_in_resource_storage,
                $signature_is_saved_in_resource_storage,
                $self_print,
                $cert_configs->returnPercentValue($this->group_ref_id),
                $logo_path,
                $page1_issuer_signature,
                $suggested_courses,
                $additional_offer,
                $final_test,
                $homework_include,
                $sessions_included,
                $firstname,
                $lastname,
                $count_individual_assessments,
                $count_completed_individual_assessments,
                $count_sessions,
                $count_attended_sessions,
                $not_suggested_courses
            );
            $rendered_certificates[] = $this->twig_template->render($arr_render);
        }

        $part_pdf->generatePDF(
            $rendered_certificates,
            $print_is_asynchronous
        );
    }

    /**
     * @param array     $courses_to_print
     * @param string    $firstname
     * @param string    $lastname
     * @param int       $user_id
     * @param bool      $print_is_asynchronous
     * @param bool|null $homework_include
     * @param bool|null $individualAssesmentsIncluded
     * @param bool|null $sessions_included
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
    #[NoReturn]
    public function parseDataMultipleCourses(
        array $courses_to_print,
        string $firstname,
        string $lastname,
        int $user_id,
        bool $print_is_asynchronous = false,
        ?bool $homework_include = true,
        ?bool $sessions_included = true
    ): void {
        global $DIC;

        $rendered_certificates = [];
        $tree = $DIC->repositoryTree();
        $part_pdf = new ilParticipationCertificatePDFGenerator();
        foreach ($courses_to_print as $course_obj ) {
            $container_ref_id = $tree->getParentId($course_obj['ref_id']);
            $count_individual_assessments = 0;
            $count_completed_individual_assessments = 0;
            $count_sessions = 0;
            $count_attended_sessions = 0;
            if ($container_ref_id !== null) {
                $new_iass_states = ilIassStatesMulti::getData($this->usr_ids, (int) $course_obj['group_id']);

                $individual_assessmentsUser = [];
                if (!empty($new_iass_states)) {
                    $individual_assessmentsUser = $new_iass_states[$user_id];
                }

                $count_individual_assessments = count(array_keys((array) $individual_assessmentsUser));
                $count_completed_individual_assessments = $this->getCompletedIndividualAssessments((array) $individual_assessmentsUser);

                $sessions = ParticipationCertificateHelper::getSessions((int) $course_obj['group_id']);

                $count_sessions = 0;
                if (!empty($sessions)) {
                    $count_sessions = count(array_keys($sessions));
                }

                foreach ($sessions as $session) {
                    $event_participants = new ilEventParticipants($session['obj_id']);
                    if($event_participants->hasParticipated($user_id)) {
                        $count_attended_sessions++;
                    }
                }
            }

            $cert_configs = new ilParticipationCertificateConfigs();
            $obj_config = $cert_configs->getObjConfigSetIfNoneCreateDefaultAndCreateNewObjConfigValues($course_obj['ref_id']);

            if (count($obj_config) > 0) {
                $global_config_id = reset($obj_config)->getGlobalConfigId();
            }

            $config_texts = $this->getConfigTexts($obj_config);

            $this->tpl->setOnScreenMessage('success', $this->pl->txt('print_done'), true);

            $new_iass_states = ilIassStatesMulti::getData($this->usr_ids, $course_obj['ref_id']);
            $user_data = ilPartCertUsersData::getData($this->pl, $this->usr_ids);
            $lo_master_course = ilLearningObjectivesMasterCrs::getData(ilObject::_lookupObjectId($course_obj['ref_id']), $this->usr_ids);

            $this->setUserData($user_data, $firstname, $lastname);

            $course_obj_id = ilObject::_lookupObjectId((int) $course_obj['ref_id']);

            $initial_test_states = ilCrsInitialTestStates::getData($this->usr_ids);
            $final_test_states = ilCrsFinalTestStates::getData($this->usr_ids, (int) $course_obj['ref_id']);
            $excercise_states = ilExcerciseStates::getData($this->usr_ids, $course_obj['ref_id']);
            $learnSugestion_results = ilLearnObjectSuggResults::getData($course_obj_id, $this->usr_ids);

            $logo_path = '';
            $logo_is_saved_in_resource_storage = false;
            if (is_numeric($global_config_id)) {
                $is_file = is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $course_obj['ref_id'], ilParticipationCertificateConfig::LOGO_FILE_NAME));

                $file = ilParticipationCertificateFiles::getFile(
                    $course_obj['ref_id'],
                    'logo'
                );

                if ($is_file && !empty($file) && !$file->getResourceStorage()) {
                    $logo_path = ilParticipationCertificateConfig::returnPicturePath('absolute', $course_obj['ref_id'], ilParticipationCertificateConfig::LOGO_FILE_NAME);
                } else if(!empty($file) && $file->getResourceStorage()) {
                    $logo_is_saved_in_resource_storage = true;
                }
            } else {
                $file = ilParticipationCertificateFiles::getFile(
                    $course_obj['ref_id'],
                    'logo'
                );

                if (!empty($file) && !$file->getResourceStorage() && is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $course_obj['ref_id'], ilParticipationCertificateConfig::LOGO_FILE_NAME))) {
                    $logo_path = ilParticipationCertificateConfig::returnPicturePath('absolute', $course_obj['ref_id'], ilParticipationCertificateConfig::LOGO_FILE_NAME);
                } elseif (!empty($file) && $file->getResourceStorage()) {
                    $logo_is_saved_in_resource_storage = true;
                }
            }

            $page1_issuer_signature = '';
            $signature_is_saved_i_resource_storage = false;
            if (is_numeric($global_config_id)) {
                $is_file = is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $course_obj['ref_id'], ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME));

                $file = ilParticipationCertificateFiles::getFile(
                    $course_obj['ref_id'],
                    'page1_issuer_signature'
                );

                if ($is_file && !empty($file) && !$file->getResourceStorage()) {
                    $page1_issuer_signature = ilParticipationCertificateConfig::returnPicturePath('absolute', $course_obj['ref_id'], ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME);

                } elseif(!empty($file) && $file->getResourceStorage()) {
                    $signature_is_saved_i_resource_storage = true;
                }
            } else {
                $file = ilParticipationCertificateFiles::getFile(
                    $course_obj['ref_id'],
                    'page1_issuer_signature'
                );

                if (!empty($file) && !$file->getResourceStorage() && is_file(ilParticipationCertificateConfig::returnPicturePath('absolute', $course_obj['ref_id'], ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME))) {
                    $page1_issuer_signature = ilParticipationCertificateConfig::returnPicturePath('absolute', $course_obj['ref_id'], ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME);
                } elseif (!empty($file) && $file->getResourceStorage()) {
                    $signature_is_saved_i_resource_storage = true;
                }
            }

            $not_suggested_courses = TrackingTool::getNotRecommendedLearningObjectivesForCertificate(
                $user_id,
                $course_obj['ref_id']
            );

            $final_test_reached_points_percent = 0;
            if (
                !empty($final_test_states[$user_id]['test_active_id']) &&
                !empty($final_test_states[$user_id]['crsqtest_qtest_tries'])
            ) {
                $final_test_reached_points_percent = $this->getFinalTestReachedPointsInPercent(
                    $course_obj['ref_id'],
                    $final_test_states[$user_id]['test_active_id'],
                    $final_test_states[$user_id]['crsqtest_qtest_tries']
                );
            }

            $arr_render = $this->fetchDataCertificate(
                $this->usr_id[0],
                $course_obj['ref_id'],
                $this->ementor,
                $config_texts,
                $user_data,
                $lo_master_course,
                $initial_test_states,
                $final_test_states,
                $final_test_reached_points_percent,
                $learnSugestion_results,
                $excercise_states,
                $new_iass_states,
                $logo_is_saved_in_resource_storage,
                $signature_is_saved_i_resource_storage,
                true,
                $cert_configs->returnPercentValue($course_obj['ref_id']),
                $logo_path,
                $page1_issuer_signature,
                $course_obj['suggested_courses'] ?? false,
                $course_obj['additional_offer'] ?? false,
                $course_obj['final_test'] ?? false,
                $homework_include,
                $sessions_included,
                $firstname,
                $lastname,
                $count_individual_assessments,
                $count_completed_individual_assessments,
                $count_sessions,
                $count_attended_sessions,
                $not_suggested_courses
            );
            $rendered_certificates[] = $this->twig_template->render($arr_render);
        }

        $part_pdf->generatePDF(
            $rendered_certificates,
            $print_is_asynchronous
        );
    }

    /**
     * @param array $individual_assessmentsUser
     * @return int
     */
    private function getCompletedIndividualAssessments(array $individual_assessmentsUser): int
    {
        $count = 0;
        foreach ($individual_assessmentsUser as $assessment_user) {
            if ($assessment_user->getPassed() === 1) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @param $obj_config
     * @return array
     */
    private function getConfigTexts($obj_config): array
    {
        $config_texts = [];
        foreach ($obj_config as $config) {
            $config_texts[$config->getConfigKey()] = $config->getConfigValue();
        }
        return $config_texts;
    }

    /**
     * @param array  $user_data
     * @param string $firstname
     * @param string $lastname
     * @return void
     */
    private function setUserData(
        array $user_data,
        string $firstname,
        string $lastname
    ): void {
        foreach ($user_data as $data) {
            if(empty($data->getPartCertFirstname())) {
                $data->setPartCertFirstname($firstname);
            }

            if(empty($data->getPartCertLastname())) {
                $data->setPartCertLastname($lastname);
            }
        }
    }

    /**
     * @param int    $user_id
     * @param int    $ref_id
     * @param bool   $ementoring
     * @param array     $config_texts
     * @param array       $user_data
     * @param array       $lo_master_course
     * @param array       $initial_test_states
     * @param array       $final_test_states
     * @param float       $final_test_reached_points_percent
     * @param array       $learn_suggestion_results
     * @param array       $excercise_states
     * @param array       $new_iass_states
     * @param array       $xali_states
     * @param bool        $logo_is_saved_in_resource_storage
     * @param bool        $signature_is_saved_in_resource_storage
     * @param bool        $self_print
     * @param string      $cert_config_value
     * @param string      $logo_path
     * @param string      $page1_issuer_signature
     * @param bool        $suggested_courses
     * @param bool        $additional_offer
     * @param bool        $final_test
     * @param bool        $homework_included
     * @param bool|null   $sessions_included
     * @param string|null $firstname
     * @param string|null $lastname
     * @param int|null    $count_individual_assessments
     * @param int|null    $count_completed_individual_assessments
     * @param int|null    $count_sessions
     * @param int|null    $count_attended_sessions
     * @param string|null $not_suggested_courses
     * @return array
     * @throws LoaderError
     * @throws SyntaxError
     * @throws ilDateTimeException
     */
    #[NoReturn]
    private function fetchDataCertificate(
        int $user_id,
        int $ref_id,
        bool $ementoring,
        array $config_texts,
        array $user_data,
        array $lo_master_course,
        array $initial_test_states,
        array $final_test_states,
        float $final_test_reached_points_percent,
        array $learn_suggestion_results,
        array $excercise_states,
        array $new_iass_states,
        bool $logo_is_saved_in_resource_storage,
        bool $signature_is_saved_in_resource_storage,
        bool $self_print,
        string $cert_config_value,
        string $logo_path,
        string $page1_issuer_signature,
        bool $suggested_courses,
        bool $additional_offer,
        bool $final_test,
        ?bool $homework_included = true,
        ?bool $sessions_included = true,
        ?string $firstname = null,
        ?string $lastname = null,
        ?int $count_individual_assessments = null,
        ?int $count_completed_individual_assessments = null,
        ?int $count_sessions = null,
        ?int $count_attended_sessions = null,
        ?string $not_suggested_courses = null
    ): array {
        $date = new ilDate(time(), IL_CAL_UNIX);
        $percentage = 0;

        //quickfix, wenn man user auswählt kann es sein, das $usr_id ein array bleibt. Das führt weiter unten zum crash. So wird das array aufgelöst.
        if (is_array($user_id)) {
            $user_id = $user_id[0];
        }
        $processed_text_values = $config_texts;

        //Preprocess text values
        foreach ($config_texts as $key => $value) {
            $twig = new Twig\Environment(new Twig\Loader\ArrayLoader());

            // Twig use the placeholders {{ }}, but plugins the  [[ ]]
            $value = $this->preparePlaceholdersForTwig($value);

            $template = $twig->createTemplate((string) $value);

            $peparsed_value = $template->render([
                'username' => ($user_data[$user_id]->getPartCertSalutation() ?
                        $user_data[$user_id]->getPartCertSalutation() . ' ' : '') .
                    ($firstname ?? $user_data[$user_id]->getPartCertFirstname()) . ' ' .
                    ($lastname ?? $user_data[$user_id]->getPartCertLastname()),
                'date' => $date->get(IL_CAL_FKT_DATE, 'd.m.Y')
            ]);

            $processed_text_values[$key] = $peparsed_value;
        }

        //Learning Objective Master Course
        $uselo_master_course = [];
        if (is_array($lo_master_course) && array_key_exists($user_id, $lo_master_course) && is_array($lo_master_course[$user_id])) {
            $uselo_master_course = $lo_master_course[$user_id];
        }

        if ($this->edited) {
            $initial_test_state = $this->array[0];
            $learn_suggest_results = $this->array[1];
            $iass_state = $this->array[2];
            $excercise_percentage = $this->array[3];
            $test_state = $initial_test_state;
        } else {

            //Initial Test
            $initial_test_state = 0;
            if (key_exists($user_id, $initial_test_states) && is_object($initial_test_states[$user_id])) {
                $initial_test_state = $initial_test_states[$user_id]->getCrsitestItestSubmitted();
            }

            $test_state = $initial_test_state;

            //Final Test
            $final_test_state = 0;
            if (key_exists($user_id, $final_test_states) && is_object($final_test_states[$user_id])) {
                $final_test_state = $final_test_states[$user_id]->getCrsqtestQtestSubmitted();
            }

            if ($final_test) {
                $test_state = $final_test_state;
            }

            //Percentage final tests of suggested modules
            $learn_suggest_results = 0;
            if (key_exists($user_id, $learn_suggestion_results) && is_object($learn_suggestion_results[$user_id])) {
                $learn_suggest_results = $learn_suggestion_results[$user_id]->getAveragePercentage(ilParticipationCertificateConfig::getConfig('calculation_type_processing_state_suggested_objectives', $ref_id), true);
            }

            //Home Work
            $excercise_percentage = 0;
            if (key_exists($user_id, $excercise_states) && is_object($excercise_states[$user_id])) {
                $excercise_percentage = $excercise_states[$user_id]->getPassedPercentage();
            }
        }

        /*Video Conferences */
        $count_passed = 0;
        $count_tests = 0;
        if (key_exists($user_id, $new_iass_states) && is_array($new_iass_states[$user_id])) {
            foreach ($new_iass_states[$user_id] as $item) {
                $count_passed = $count_passed + $item->getPassed();
                $count_tests = $count_tests + $item->getTotal();
            }
        }

        if ($count_tests > 0) {
            $percentage = $count_passed / $count_tests * 100;

            switch ($count_tests) {
                case 1:
                    if ($count_passed == 1) {
                        $iass_states = "<img alt='' src=" . ILIAS_ABSOLUTE_PATH . "/" . $this->pl->getImagePath("passed_s.png") . ">";
                    } else {
                        $iass_states = "<img alt='' src=" . ILIAS_ABSOLUTE_PATH . "/" . $this->pl->getImagePath("failed_s.png") . ">";
                    }
                    break;
                default:
                    $iass_states = $count_passed . '/' . $count_tests;
                    break;
            }
        } else {
            $iass_states = "<img alt='' src=" . ILIAS_ABSOLUTE_PATH . "/" . $this->pl->getImagePath("not_attempted_s.png") . ">";
        }

        if ($logo_is_saved_in_resource_storage) {
            if(!empty($processed_text_values['logo'])) {
                $file = new ilParticipationCertificateFiles();
                $src = $file->getFileSrcByStorageType(
                    $processed_text_values['logo'],
                    $ref_id,
                    'logo'
                );

                $logo_path = $src;
            }

        }

        if ($signature_is_saved_in_resource_storage && !empty($processed_text_values['page1_issuer_signature'])) {
            $file = new ilParticipationCertificateFiles();
            $src = $file->getFileSrcByStorageType(
                $processed_text_values['page1_issuer_signature'],
                $ref_id,
                'page1_issuer_signature'
            );

            $page1_issuer_signature = $src;
        }

        if (!$self_print) {
            $suggested_courses = true;
            $additional_offer = true;
            $final_test = true;
        }

        if ($ementoring) {
            $ementoring = (bool) ilParticipationCertificateConfig::getConfig('enable_ementoring', $ref_id);
        }

        $data = [
            'text_values' => $processed_text_values,
            'show_ementoring' => $ementoring,
            'show_footer' => $this->footer,
            'arr_lo_master_crs' => $uselo_master_course,
            'crsitest_itest_submitted' => $test_state,
            'learn_sugg_reached_percentage' => $learn_suggest_results,
            'iass_state' => $percentage,
            'iass_states' => $iass_states,
            'excercise_percentage' => $excercise_percentage,
            'logo_path' => $logo_path,
            'page1_issuer_signature' => $page1_issuer_signature,
            'standard_value' => $cert_config_value,
            'sessions' => [
                'label' => $this->pl->txt('sessions'),
                'value' => ($count_attended_sessions + $count_completed_individual_assessments) . '/' . ($count_sessions + $count_individual_assessments)
            ],
            'homework_included' => $homework_included,
            'sessions_included' => $sessions_included,
            'suggested_courses' => $suggested_courses,
            'additional_offer' => $additional_offer,
            'final_test' => $final_test,
            'final_test_reached_percent' => $final_test_reached_points_percent . '%',
            'not_suggested_courses' => [
                'label' => $this->pl->txt('not_suggested_learning_modules'),
                'value' => $not_suggested_courses
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

    /**
     * @param int $obj_id
     * @return array
     */
    public function getDataFinalTest(int $obj_id): array
    {
        global $DIC;

        $ilDB = $DIC->database();

        $result = $ilDB->queryF(
            "SELECT * FROM loc_settings
              WHERE obj_id = %s AND qtest IS NOT NULL",
            ['integer'],
            [$obj_id]
        );

        $data = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $data = $row;
        }

        return $data;
    }
}
