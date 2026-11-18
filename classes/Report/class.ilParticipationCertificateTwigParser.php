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

            // no row at all (e.g. udf_* config missing) counts as missing data, too
            if (!isset($arr_usr_data[$usrId]) || !$user_data->checkIfUserDataFilled(
                $arr_usr_data[$usrId]->getPartCertFirstname(),
                $arr_usr_data[$usrId]->getPartCertLastname()
            )) {
                unset($user_ids[$key]);
            }
        }
        return array_values($user_ids);
    }

    private function getFinalTestEvaluationData(int $course_obj_id): ilTestEvaluationData
    {
        $test_ref_id = ilLOSettings::getInstanceByObjId($course_obj_id)->getTestByType(ilLOSettings::TYPE_TEST_QUALIFIED);

        return (new ilObjTest($test_ref_id, true))->getCompleteEvaluationData();
    }

    private function getFinalTestReachedPointsInPercent(
        ilTestEvaluationData $test_data,
        int $test_active_id,
        int $test_tries
    ): float {
        return $test_data->getParticipant($test_active_id)?->getPass($test_tries - 1)?->getReachedPointsInPercent() ?? 0.0;
    }

    /**
     * @return array{0: string, 1: bool} file path (empty if none) and whether the picture is in the resource storage
     */
    private function getPicture(int $ref_id, string $file_type, string $file_name): array
    {
        $file = ilParticipationCertificateFiles::getFile($ref_id, $file_type);
        if (empty($file)) {
            return ['', false];
        }
        if ($file->getResourceStorage()) {
            return ['', true];
        }
        $path = ilParticipationCertificateConfig::returnPicturePath('absolute', $ref_id, $file_name);

        return [is_file($path) ? $path : '', false];
    }

    /**
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
        $ref_id = (int) $this->group_ref_id;

        $this->renderCertificates(
            [[
                'ref_id' => $ref_id,
                'group_ref_id' => ParticipationCertificateHelper::getGroupRefId($course_ref_id ?? $ref_id),
                'suggested_courses' => (bool) $suggested_courses,
                'additional_offer' => (bool) $additional_offer,
                'final_test' => (bool) $final_test,
            ]],
            $self_print,
            $print_is_asynchronous,
            $homework_include,
            $sessions_included,
            $firstname,
            $lastname,
            !empty($firstname) && !empty($lastname)
        );
    }

    /**
     * @param array $courses_to_print each with ref_id, group_id and the options suggested_courses, additional_offer, final_test
     * @param int   $user_id          unused, the printed user is the one passed to the constructor
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
        $courses = [];
        foreach ($courses_to_print as $course_obj) {
            $courses[] = [
                'ref_id' => (int) $course_obj['ref_id'],
                'group_ref_id' => isset($course_obj['group_id']) ? (int) $course_obj['group_id'] : null,
                'suggested_courses' => !empty($course_obj['suggested_courses']),
                'additional_offer' => !empty($course_obj['additional_offer']),
                'final_test' => !empty($course_obj['final_test']),
            ];
        }

        $this->renderCertificates(
            $courses,
            true,
            $print_is_asynchronous,
            $homework_include,
            $sessions_included,
            $firstname,
            $lastname,
            true
        );
    }

    /**
     * Data is loaded once per course, not per user.
     *
     * @param array<int, array{ref_id: int, group_ref_id: ?int, suggested_courses: bool, additional_offer: bool, final_test: bool}> $courses
     * @throws CrossReferenceException
     * @throws LoaderError
     * @throws MpdfException
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws SyntaxError
     * @throws arException
     * @throws ilDateTimeException
     */
    private function renderCertificates(
        array $courses,
        bool $self_print,
        bool $print_is_asynchronous,
        ?bool $homework_include,
        ?bool $sessions_included,
        ?string $firstname,
        ?string $lastname,
        bool $fill_missing_names
    ): void {
        $this->tpl->setOnScreenMessage('success', $this->pl->txt('print_done'), true);

        $usr_ids_to_print = $self_print
            ? $this->usr_id
            : $this->excludeUsersFromPrintIfMissingUserData($this->usr_id);

        $user_data = ilPartCertUsersData::getData($this->pl, $usr_ids_to_print);
        if ($fill_missing_names) {
            $this->setUserData($user_data, (string) $firstname, (string) $lastname);
        }

        $cert_configs = new ilParticipationCertificateConfigs();
        $rendered_certificates = [];
        foreach ($courses as $course) {
            $ref_id = $course['ref_id'];
            // without a group, sessions are taken from the course
            $group_ref_id = (int) ($course['group_ref_id'] ?? $ref_id);
            $course_obj_id = ilObject::_lookupObjectId($ref_id);

            $config_texts = $this->getConfigTexts(
                $cert_configs->getObjConfigSetIfNoneCreateDefaultAndCreateNewObjConfigValues($ref_id)
            );
            $percent_value = $cert_configs->returnPercentValue($ref_id);
            $calculation_type = ilParticipationCertificateConfig::getConfig(
                'calculation_type_processing_state_suggested_objectives',
                $ref_id
            );
            $show_ementoring = $this->ementor
                && (bool) ilParticipationCertificateConfig::getConfig('enable_ementoring', $ref_id);
            [$logo_path, $logo_is_saved_in_resource_storage] = $this->getPicture(
                $ref_id,
                'logo',
                ilParticipationCertificateConfig::LOGO_FILE_NAME
            );
            [$page1_issuer_signature, $signature_is_saved_in_resource_storage] = $this->getPicture(
                $ref_id,
                'page1_issuer_signature',
                ilParticipationCertificateConfig::ISSUER_SIGNATURE_FILE_NAME
            );

            // IASS may lie in the group and directly in the course
            $new_iass_states = ilIassStatesMulti::getData(
                $usr_ids_to_print,
                array_values(array_unique(array_filter([(int) $course['group_ref_id'], $ref_id])))
            );
            $sessions = ParticipationCertificateHelper::getSessions($group_ref_id);
            $attended_sessions = ParticipationCertificateHelper::countAttendedSessions($sessions, $usr_ids_to_print);
            $lo_master_course = ilLearningObjectivesMasterCrs::getData($course_obj_id, $usr_ids_to_print);
            $initial_test_states = ilCrsInitialTestStates::getData(
                $usr_ids_to_print,
                (new ilParticipationCertificateAccess($ref_id))->getCourseRefId()
            );
            $final_test_states = ilCrsFinalTestStates::getData($usr_ids_to_print, $ref_id);
            $excercise_states = ilExcerciseStates::getData($usr_ids_to_print, $ref_id);
            $learn_sugg_results = ilLearnObjectSuggResults::getData($course_obj_id, $usr_ids_to_print);
            $final_test_data = null;
            $not_recommended_objectives = TrackingTool::getNotRecommendedLearningObjectivesForCertificates($usr_ids_to_print, $ref_id);

            foreach ($usr_ids_to_print as $usr_id) {
                $usr_id = (int) $usr_id;

                $count_individual_assessments = 0;
                $count_completed_individual_assessments = 0;
                if (!empty($new_iass_states[$usr_id])) {
                    $count_individual_assessments = count((array) $new_iass_states[$usr_id]);
                    $count_completed_individual_assessments = $this->getCompletedIndividualAssessments((array) $new_iass_states[$usr_id]);
                }

                $final_test_reached_points_percent = 0.0;
                if (
                    !empty($final_test_states[$usr_id]['test_active_id']) &&
                    !empty($final_test_states[$usr_id]['crsqtest_qtest_tries'])
                ) {
                    $final_test_data ??= $this->getFinalTestEvaluationData($course_obj_id);
                    $final_test_reached_points_percent = round($this->getFinalTestReachedPointsInPercent(
                        $final_test_data,
                        (int) $final_test_states[$usr_id]['test_active_id'],
                        (int) $final_test_states[$usr_id]['crsqtest_qtest_tries']
                    ), 0);
                }

                $arr_render = $this->fetchDataCertificate(
                    $usr_id,
                    $show_ementoring,
                    $calculation_type,
                    $config_texts,
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
                    $percent_value,
                    $logo_path,
                    $page1_issuer_signature,
                    $course['suggested_courses'],
                    $course['additional_offer'],
                    $course['final_test'],
                    $homework_include,
                    $sessions_included,
                    $firstname,
                    $lastname,
                    $count_individual_assessments,
                    $count_completed_individual_assessments,
                    count($sessions),
                    $attended_sessions[$usr_id] ?? 0,
                    $not_recommended_objectives[$usr_id] ?? '0/0'
                );
                $rendered_certificates[] = $this->twig_template->render($arr_render);
            }
        }

        (new ilParticipationCertificatePDFGenerator())->generatePDF(
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
            if (empty($data->getPartCertFirstname())) {
                $data->setPartCertFirstname($firstname);
            }

            if (empty($data->getPartCertLastname())) {
                $data->setPartCertLastname($lastname);
            }
        }
    }

    /**
     * @param int    $user_id
     * @param bool   $ementoring       already combined with the course config enable_ementoring
     * @param string|null $calculation_type
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
        bool $ementoring,
        ?string $calculation_type,
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
        // The texts are editable by course admins, so they must never be compiled as Twig
        // (that would allow template injection / code execution). Only the known
        // placeholders are replaced; escaping happens once in certificate.html.
        $placeholder_values = [
            'username' => ($user_data[$user_id]->getPartCertSalutation() ?
                    $user_data[$user_id]->getPartCertSalutation() . ' ' : '') .
                ($firstname ?? $user_data[$user_id]->getPartCertFirstname()) . ' ' .
                ($lastname ?? $user_data[$user_id]->getPartCertLastname()),
            'date' => $date->get(IL_CAL_FKT_DATE, 'd.m.Y')
        ];

        foreach ($config_texts as $key => $value) {
            $processed_text_values[$key] = $this->replacePlaceholders((string) $value, $placeholder_values);
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
                $learn_suggest_results = $learn_suggestion_results[$user_id]->getAveragePercentage($calculation_type, true);
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
                        $iass_states = $this->buildStatusIconTag('passed_s.png');
                    } else {
                        $iass_states = $this->buildStatusIconTag('failed_s.png');
                    }
                    break;
                default:
                    $iass_states = $count_passed . '/' . $count_tests;
                    break;
            }
        } else {
            $iass_states = $this->buildStatusIconTag('not_attempted_s.png');
        }

        if ($logo_is_saved_in_resource_storage) {
            if (!empty($processed_text_values['logo'])) {
                $logo_path = ilParticipationCertificateFiles::getImageDataUri((string) $processed_text_values['logo']);
            }

        }

        if ($signature_is_saved_in_resource_storage && !empty($processed_text_values['page1_issuer_signature'])) {
            $page1_issuer_signature = ilParticipationCertificateFiles::getImageDataUri(
                (string) $processed_text_values['page1_issuer_signature']
            );
        }

        if (!$self_print) {
            $suggested_courses = true;
            $additional_offer = true;
            $final_test = true;
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
     * @param string $value
     * @return array|string|string[]
     *
     * <img> for a status icon in the PDF (rendered with |raw in certificate.html).
     * getImagePath() is already an absolute filesystem path (ilPluginInfo::getPath() is based on
     * ilComponentRepository::PLUGIN_BASE_PATH), so it must not be prefixed with ILIAS_ABSOLUTE_PATH:
     * mPDF would not find the file and fall back to an http URL, which whitelistStreamWrappers blocks.
     */
    private function buildStatusIconTag(string $image_name): string
    {
        return '<img alt="" src="'
            . htmlspecialchars($this->pl->getImagePath($image_name), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '">';
    }

    /**
     * Replaces [[name]] (and the old-style {{name}}) placeholders in a stored certificate text.
     * Known names get their value; any other placeholder expression is removed, as Twig
     * rendered unknown variables as empty. Nothing is evaluated, and replaced values are
     * not scanned again, so a value containing "[[...]]" stays literal.
     *
     * @param array<string, string> $values
     */
    private function replacePlaceholders(string $text, array $values): string
    {
        return preg_replace_callback(
            '/\[\[\s*(.*?)\s*\]\]|\{\{\s*(.*?)\s*\}\}/s',
            static function (array $match) use ($values): string {
                $name = ($match[1] ?? '') !== '' ? $match[1] : ($match[2] ?? '');
                return $values[$name] ?? '';
            },
            $text
        ) ?? $text;
    }
}
