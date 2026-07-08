<?php
class ilParticipationCertificateSingleResultTableGUI extends ilTable2GUI {

	const IDENTIFIER = 'usr_id';
	const SUCCESSFUL_PROGRESS_CSS_CLASS = "ilCourseObjectiveProgressBarCompleted";
	const NON_SUCCESSFUL_PROGRESS_CSS_CLASS = "ilCourseObjectiveProgressBarNeutral";
	const FAILED_PROGRESS_CSS_CLASS = "ilCourseObjectiveProgressBarFailed";

	protected ilTabsGUI $tabs;

	protected ilCtrl $ctrl;

	protected ?object $parent_obj;

	protected ilParticipationCertificatePlugin $pl;

	protected array $filter = array();

	protected int $usr_id;

	protected array $marks = array();

	protected string $color;

	protected array $usr_ids;

    private string $unsugg_color;

	/**
	 * @var ilLearningObjectivesMasterCrs[]
	 */
	protected array $sugg;

    private int $courseObjId;

    /**
     * @throws ilCtrlException
     * @throws ilException
     */
    public function __construct(ilParticipationCertificateMultipleResultGUI|ilParticipationCertificateResultGUI|ilParticipationCertificateSingleResultGUI $a_parent_obj, string $a_parent_cmd, int $usr_id) {
		global $DIC;

		$this->ctrl = $DIC->ctrl();
		$this->tabs = $DIC->tabs();
		$this->pl = ilParticipationCertificatePlugin::getInstance();

		$config = ilParticipationCertificateConfig::where(array(
			'config_key' => 'color',
			'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL,
		))->first();
		$this->color = $config->getConfigValue();

		$unsugg_config = ilParticipationCertificateConfig::where(array(
			'config_key' => 'unsugg_color',
			'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL,
		))->first();
		if (is_Object($unsugg_config)) { 
			$this->unsugg_color = $unsugg_config->getConfigValue();
		} else {
			$this->unsugg_color = '909090';
		}

		$this->setPrefix('dhbw_part_cert_res');
		$this->setFormName('dhbw_part_cert_res');
		$this->setId('dhbw_part_cert_res');

		$this->setLimit(0);
		$this->setShowRowsSelector(false);

		$this->ctrl->saveParameterByClass(ilParticipationCertificateResultModificationGUI::class, [ 'ref_id', 'group_id' ]);
		$this->ctrl->saveParameterByClass(ilParticipationCertificateResultGUI::class, 'usr_id');


        $this->courseObjId = ilObject::_lookupObjId($_GET['ref_id']);

		$cert_access = new ilParticipationCertificateAccess($_GET['ref_id']);
		$this->usr_ids = $cert_access->getUserIdsOfGroup();
		$this->usr_id = $usr_id;
		$this->sugg = getLearnSuggs::getData($usr_id, $this->courseObjId);

		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->getEnableHeader();
		$this->setRowTemplate('tpl.default_row_single.html', $this->pl->getDirectory());
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj));

		$arr_usr_data = ilPartCertUsersData::getData($this->pl, $this->usr_ids);
		$nameUser = $arr_usr_data[$usr_id]->getPartCertFirstname() . ' ' . $arr_usr_data[$usr_id]->getPartCertLastname();
		if ($nameUser == ' ') {
			$nameUser = $this->pl->txt('loginname') . ' ' . $arr_usr_data[$usr_id]->getPartCertUserName();
		}

		$this->setTitle($this->pl->txt('result_for ') . ' ' . $nameUser);

		$this->addColumns();
		$this->parseData();
	}

	function getSelectableColumns(): array
    {
		$cols = array();

		/*$finalTestsStates = ilLearnObjectFinalTestStates::getData([$this->usr_id ]);*/
        $finalTestsStates = ilLearnObjectFinalTestStates::getDataByCourseObjId($this->courseObjId, [$this->usr_id ]);
		$sorted = $this->sortColumns();
		$i = 0;

		if (count($finalTestsStates)) {
            foreach ($sorted as $sort_key => $sort_arr) {
                if (array_key_exists($sort_key, $finalTestsStates[$this->usr_id])) {
                    /** @var ilLearnObjectFinalTestState $finalTestsState */

                    /** @var ilLearnObjectFinalTestState $finalTestsState */
                    $finalTestsStates_course = $finalTestsStates[$this->usr_id][$sort_key];

                    foreach ($finalTestsStates_course as $finalTestsState) {
                        $cols[$finalTestsState->getLocftestCrsObjId()] = array(
                            'txt' => $finalTestsState->getLocftestLearnObjectiveTitle(),
                            'obj_id' => $sorted[$sort_key]['obj_id'],
                            'objective_id' => $sorted[$sort_key]['objective_id'],
                            'default' => true,
                            'width' => 'auto',
                        );
                    }

                }

                $i = $i + 1;

                if ($i == 10000) {
                    break;
                }
            }
        }


		return $cols;
	}

	function sortColumns(): array
    {
		//First sort scores
		$scores = NewLearningObjectiveScores::getData($this->usr_id, $this->courseObjId);
		//if the scores are equal, sort because of the weight value
		$weights = getFineWeights::getData($this->courseObjId);
		$newWeights = (array)$weights;

		$sorting = array();

		foreach ($scores as $score) {

			/**
			 * @var NewLearningObjectiveScore $score
			 */

			if (key_exists('weight_fine_'.$score->getObjectiveId(),$newWeights)) {
				$fine = $newWeights['weight_fine_'.$score->getObjectiveId()];
			} else {
				//fallback
				$fine = 1;
			}

			$sorting[$score->getObjectiveId()] = [
				'title' => $score->getTitle(),
				'score' => $score->getScore(),
				'obj_id' => $score->getCourseObjId(),
				'objective_id' => $score->getObjectiveId(),
				'weight' => $fine
			];
		}

		return $sorting;
	}

	private function addColumns(): void
    {

		foreach ($this->getSelectableColumns() as $k => $v) {
			if ($this->isColumnSelected($k)) {
				if (isset($v['sort_field'])) {
					$sort = $v['sort_field'];
				} else {
					$sort = '';
				}
				$this->addColumn($v['txt'], $sort, $v['width']);
			}
		}
	}

	public function parseData(): array
    {
        /*$arr_FinalTestsStates = ilLearnObjectFinalTestStates::getData([$this->usr_id]);*/
        $arr_FinalTestsStates = ilLearnObjectFinalTestStates::getDataByCourseObjId($this->courseObjId, [$this->usr_id]);
		$usr_id = $this->usr_id;
		$rec_array = array();
        $processed = array();


		if (count($arr_FinalTestsStates)) {

            $row_key = [];
			//build_row_key_s
			foreach ($arr_FinalTestsStates[$usr_id] as $rec) {
				/**
				 * @var ilLearnObjectFinalTestState $rec;
				 */
				$row_key[$rec[0]->getLocftestCrsObjId()] = 0;
			}

			foreach ($arr_FinalTestsStates[$usr_id] as $rec_final_tests) {
				/**
				 * @var ilLearnObjectFinalTestState $rec_final_test;
				 */

				foreach($rec_final_tests as $key => $value) {
                    $locfTestCrsObjId = $value->getLocftestCrsObjId();

                    if ($locfTestCrsObjId) {
						// check if data already exists
						$crs_obj_id = $locfTestCrsObjId;
						$crs_objective_id = $value->getLocftestObjectiveId();
						if (isset($processed[$crs_obj_id])) {
							if (isset($processed[$crs_obj_id][$crs_objective_id])) {
								continue;
							}
						}

                        $rec_array[$row_key[$locfTestCrsObjId]][$locfTestCrsObjId] = $value->getLocftestObjectiveTitle();

                        $row_key[$locfTestCrsObjId] += 1;
                        //second line - array progressbar
                        $rec_array[$row_key[$locfTestCrsObjId]][$locfTestCrsObjId][0] = $value->getLocftestPercentage();
                        $rec_array[$row_key[$locfTestCrsObjId]][$locfTestCrsObjId][1] = $value->getLocftestQplsRequiredPercentage();
                        $rec_array[$row_key[$locfTestCrsObjId]][$locfTestCrsObjId][2] = 1;

                        $row_key[$locfTestCrsObjId] += 1;

						$processed[$crs_obj_id][$crs_objective_id] = $usr_id;
                    }
                }
			}
		}

        $this->setData($rec_array);

		return $rec_array;
	}

	protected function buildProgressBar($points, ?int $required_percent, ?int $tries): string
    {
		$points = $points[0];
		$tooltip_id = "prg_";

		$maximum_possible_amount_of_points = 100;
		$current_amount_of_points = (int)$points;

		if ($maximum_possible_amount_of_points > 0) {
            $current_percent = (int)($current_amount_of_points * 100 / $maximum_possible_amount_of_points);
		} else {
			$current_percent = 0;
		}

		//required to dodge bug in ilContainerObjectiveGUI::renderProgressBar
        if ($required_percent == 0) {
            $required_percent = null;
        }

		if ($current_percent >= $required_percent) {
			$css_class = self::SUCCESSFUL_PROGRESS_CSS_CLASS;
		} elseif ($tries == NULL) {
			$css_class = self::NON_SUCCESSFUL_PROGRESS_CSS_CLASS;
		} else {
			$css_class = self::FAILED_PROGRESS_CSS_CLASS;

            if ($current_percent === 0) {
                $css_class .= ' percent-0';
            }
		}

        if (is_float($required_percent)) {
            $required_percent = (int) round($required_percent);
        }

		return \ilContainerObjectiveGUI::renderProgressBar(
            $current_percent,
            $required_percent,
            $css_class,
            '',
            NULL,
            $tooltip_id,
            ''
        );
	}

	public function fillRow(array $a_set): void
    {

		foreach ($this->getSelectableColumns() as $k => $v) {

			if ($this->isColumnSelected($k)) {
				if (isset($a_set[$k])) {
					$this->tpl->setCurrentBlock('td');
					if (is_array($a_set[$k])) {

						$this->tpl->setVariable('COURSE', $this->buildProgressBar(explode('%', $a_set[$k][0]  ?? ''), $a_set[$k][1], $a_set[$k][2]));
					} else {
						$this->tpl->setVariable('COURSE', $a_set[$k]);
					}
					if ($this->searchForId($v['objective_id'], $this->sugg)) {
						$this->tpl->setVariable('COLOR', $this->color);
					} else {
						$this->tpl->setVariable('COLOR', $this->unsugg_color);
					}
					$this->tpl->parseCurrentBlock();
				} else {
					$this->tpl->setCurrentBlock('td');
					$this->tpl->setVariable('COURSE', '&nbsp;');
					if ($this->searchForId($v['objective_id'], $this->sugg)) {
						$this->tpl->setVariable('COLOR', $this->color);
					} else {
						$this->tpl->setVariable('COLOR', $this->unsugg_color);
					}
					$this->tpl->parseCurrentBlock();
				}
			}
		}
	}

	function searchForId(int $id, array $array): bool
    {
		foreach ($array as $key => $val) {
			if ($val->getSuggObjectiveId() == $id) {
				return true;
			}
		}

		return false;
	}
}
