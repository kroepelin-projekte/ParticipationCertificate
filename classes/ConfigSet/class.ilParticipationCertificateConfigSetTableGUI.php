<?php

use ILIAS\Data\Factory;
use ILIAS\Data\DateFormat\DateFormat;
use ILIAS\UI\Component\Table as I;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use ILIAS\UI\URLBuilder;
use ILIAS\Data\URI;
use ILIAS\UI\URLBuilderToken;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\UI\Implementation\Component\Table\Data;

class ilParticipationCertificateConfigSetTableGUI
{
    protected Factory $df;
    protected DateFormat $current_user_date_format;

    protected ilParticipationCertificatePlugin $pl;

    protected URLBuilderToken $action_parameter_token;

    protected URLBuilderToken $row_id_token;

    protected URLBuilderToken $config_type;

    private \ILIAS\UI\Factory $ui_factory;

    public function __construct()
    {
        global $DIC;

        $this->ui_factory = $DIC->ui()->factory();
        $this->df = new Factory();
        $this->current_user_date_format = $this->df->dateFormat()->withTime24(
            $DIC->user()->getDateFormat()
        );
        $this->pl = ilParticipationCertificatePlugin::getInstance();
    }

    /**
     * @throws ilCtrlException
     */
    public function getTableForRepresentation(): I\Data
    {
        global $DIC;

        $actions = $this->getActions();

        $data_retrieval = $this->getDataRetrieval();

        $columns = $this->getColumsForRepresentation();

        $table = $this->ui_factory->table()->data($data_retrieval, '', $columns)
             ->withActions($actions)
             ->withRequest($DIC->http()->request());

        return $table;
    }

    /**
     * @return DataRetrieval
     */
    private function getDataRetrieval(): I\DataRetrieval
    {
        $data_retrieval = new class () implements DataRetrieval {

            /**
             * @param DataRowBuilder $row_builder
             * @param array          $visible_column_ids
             * @param Range          $range
             * @param Order          $order
             * @param mixed          $additional_viewcontrol_data
             * @param mixed          $filter_data
             * @param mixed          $additional_parameters
             * @return Generator
             */
            public function getRows(
                I\DataRowBuilder $row_builder,
                array $visible_column_ids,
                Range $range,
                Order $order,
                mixed $additional_viewcontrol_data,
                mixed $filter_data,
                mixed $additional_parameters
            ): \Generator {
                global $DIC;

                $pl = ilParticipationCertificatePlugin::getInstance();
                $ui_factory = $DIC->ui()->factory();

                $records = $this->getRecords($range, $order);

                foreach ($records as $idx => $record) {
                    switch ($record['configset_type']) {
                        case  ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL:
                            yield $row_builder->buildDataRow($record['config_id'] . '_' . $record['config_type']. '_' . $record['obj_ref_id'], $record)
                                              ->withDisabledAction('copy')
                                              ->withDisabledAction('delete')
                                              ->withDisabledAction('activate')
                                              ->withDisabledAction('deactivate')
                                              ->withDisabledAction('go_to')
                                              ->withDisabledAction('create_template');;
                            break;
                        case  ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE:

                            $build_row = $row_builder->buildDataRow($record['config_id'] . '_' . $record['config_type']. '_' . $record['obj_ref_id'], $record)
                                                     ->withDisabledAction('go_to')
                                                     ->withDisabledAction('create_template');

                            if ($record['order_by'] == 1) {

                                $build_row = $build_row->withDisabledAction('delete')
                                                       ->withDisabledAction('activate')
                                                       ->withDisabledAction('deactivate');
                            }

                            if ($record['active_status']) {
                                yield $build_row->withDisabledAction('activate');
                            } else {
                                yield $build_row->withDisabledAction('deactivate');
                            }

                            break;
                        case  ilParticipationCertificateConfig::CONFIG_SET_TYPE_GROUP:
                            yield $row_builder->buildDataRow($record['config_id'] . '_' . $record['config_type'] . '_' . $record['obj_ref_id'], $record)
                                              ->withDisabledAction('edit')
                                              ->withDisabledAction('copy')
                                              ->withDisabledAction('delete')
                                              ->withDisabledAction('activate')
                                              ->withDisabledAction('deactivate');

                            break;
                    }
                }
            }

            /**
             * @param mixed $additional_viewcontrol_data
             * @param mixed $filter_data
             * @param mixed $additional_parameters
             * @return int|null
             */
            public function getTotalRowCount(
                mixed $additional_viewcontrol_data,
                mixed $filter_data,
                mixed $additional_parameters
            ): ?int {
                return count($this->getRecords());
            }

            /**
             * @param Range|null $range
             * @param Order|null $order
             * @return array
             */
            protected function getRecords(?Range $range = null, ?Order $order = null): array
            {
                global $DIC;

                $pl = ilParticipationCertificatePlugin::getInstance();

                $global_configs = new ilParticipationCertificateConfigSets();
                $data = $global_configs->getAllConfigSets();

                $tableData = [];

                foreach ($data as $config_set) {
                    $active = 'inactive';
                    $active_status = false;
                    $config_set_type = '';
                    $config_id = $config_set['conf_id'];
                    $config_type = $config_set['configset_type'];
                    $arr_type = [];
                    $obj_ref_id = $config_set['obj_ref_id'];

                    foreach ($config_set as $key => $value) {
                        switch ($key) {
                            case 'configset_type':
                                if ($config_set[$key] > 0) {
                                    switch ($config_set['configset_type']) {
                                        case ilParticipationCertificateConfig::CONFIG_SET_TYPE_GROUP:
                                            if (!ilParticipationCertificateGlobalConfigSet::find(
                                                $config_set['object_gl_conf_template_id']
                                            )) {
                                                $config_set_type = '';
                                                break;
                                            }
                                            $arr_type[] = $pl->txt('configset_type_' . $config_set['configset_type']);
                                            $arr_type[] = $pl->txt(
                                                'object_config_type_' . $config_set['object_config_type']
                                            );
                                            $template = new ilParticipationCertificateGlobalConfigSet(
                                                $config_set['object_gl_conf_template_id']
                                            );
                                            $arr_type[] = $pl->txt('origin_template') . ": " . $template->getTitle();

                                            $config_set_type = implode("<br/>", $arr_type);
                                            break;
                                        default:
                                            $config_set_type = $pl->txt('configset_type_' . $config_set['configset_type']);
                                            break;
                                    }
                                } else {
                                    $config_set_type = '';
                                }

                                break;

                            case 'active':
                                if ((int)$config_set[$key] === 1) {
                                    $active = 'active';
                                    $active_status = true;
                                }
                                break;
                            default:

                                break;
                        }
                    }

                    $tmp = [
                        'config_id' => $config_id,
                        'config_type' => $config_type,
                        'order_by' => $config_set['order_by'],
                        'configset_type' => $config_set['configset_type'],
                        'configset_type_title' => $config_set_type,
                        'title' => $config_set['title'],
                        'parent_title' => $config_set['parent_title'],
                        'obj_ref_id' => $obj_ref_id,
                        'active' => $active,
                        'active_status' => $active_status
                    ];

                    $tableData[] = $tmp;
                }

                return $tableData;
            }
        };

        return $data_retrieval;
    }

    /**
     * @param $pl
     * @return array
     */
    private function getSelectableColumns(): array
    {
        $cols = array();
        $cols['configset_type'] = array( 'txt' => $this->pl->txt('config_type'), 'default' => '', 'width' => 'auto' );
        $cols['title'] = array( 'txt' => $this->pl->txt('title'), 'default' => '', 'width' => 'auto' );
        $cols['parent_title'] = array( 'txt' => $this->pl->txt('parent_title'), 'default' => '', 'width' => 'auto' );
        $cols['active'] = array( 'txt' => $this->pl->txt('active'), 'default' => false, 'width' => 'auto' );

        return $cols;
    }

    /**
     * @param $pl
     * @return array
     */
    protected function getColumsForRepresentation(): array
    {
        $columns = $this->getSelectableColumns();

        $ui_factory = $this->ui_factory;

        return  [
            'configset_type_title' => $ui_factory->table()->column()->text($columns['configset_type']['txt'])
                                                 ->withIsSortable(false),
            'title' => $ui_factory->table()->column()->text($columns['title']['txt'])
                                  ->withIsSortable(false),
            'parent_title' => $ui_factory->table()->column()->text($columns['parent_title']['txt'])
                                         ->withIsSortable(false),
            'active' => $ui_factory->table()->column()->text($columns['active']['txt'])
                                   ->withIsSortable(false),
        ];
    }

    /**
     * @throws ilCtrlException
     */
    private function getActions(): array
    {
        global $DIC;

        $factory = $DIC->ui()->factory();
        $uri = $this->buildURI();
        $url_builder = new URLBuilder($uri);

        [$url_builder, $this->action_parameter_token, $this->row_id_token] =
            $url_builder->acquireParameters(
                ['config'],
                'action',
                'entry'
            );

        $actions = [
            'edit' => $factory->table()->action()->single(
                $this->pl->txt('edit'),
                $url_builder->withParameter($this->action_parameter_token, 'edit'),
                $this->row_id_token
            ),
            'copy' => $factory->table()->action()->single(
                $this->pl->txt('copy'),
                $url_builder->withParameter($this->action_parameter_token, 'copy'),
                $this->row_id_token
            ),
            'delete' =>
                $factory->table()->action()->single(
                    $this->pl->txt('delete'),
                    $url_builder->withParameter($this->action_parameter_token, 'delete'),
                    $this->row_id_token
                ),
            'activate' =>
                $factory->table()->action()->single(
                    $this->pl->txt('set_active'),
                    $url_builder->withParameter($this->action_parameter_token, 'activate'),
                    $this->row_id_token
                ),
            'deactivate' =>
                $factory->table()->action()->single(
                    $this->pl->txt('set_inactive'),
                    $url_builder->withParameter($this->action_parameter_token, 'deactivate'),
                    $this->row_id_token
                ),
            'create_template' =>
                $factory->table()->action()->single(
                    $this->pl->txt('create_template'),
                    $url_builder->withParameter($this->action_parameter_token, 'create-template'),
                    $this->row_id_token
                ),
            'go_to' =>
                $factory->table()->action()->single(
                    $this->pl->txt('go_to_object'),
                    $url_builder->withParameter($this->action_parameter_token, 'go-to'),
                    $this->row_id_token
                )
        ];
        return $actions;
    }

    /**
     * @return URI
     * @throws ilCtrlException
     */
    private function buildURI(): URI {
        global $DIC;

        return new URI(
            ILIAS_HTTP_PATH . '/' . $DIC->ctrl()->getLinkTargetByClass(
                \ilParticipationCertificateConfigGUI::class,
                ilParticipationCertificateConfigGUI::CMD_ACTION
            )
        );
    }
}