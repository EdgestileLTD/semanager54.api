<?php

namespace SE\Shop;

use SE\DB;
use SE\Exception;

// особенность?
class Feature extends Base
{
    protected $tableName = "shop_feature";
    protected $sortBy = "sort";
    protected $sortOrder = "asc";

    // получить настройки
    protected function getSettingsFetch()
    {
        $this->debugging('funct', __FUNCTION__.' '.__LINE__, __CLASS__, '[comment]');
        return array(
            "select" => 'sf.*, sfg.name name_group',
            "joins" => array(
                array(
                    "type" => "left",
                    "table" => 'shop_feature_group sfg',
                    "condition" => 'sfg.id = sf.id_feature_group'
                ),
                array(
                    "type" => "left",
                    "table" => 'shop_group_feature sgf',
                    "condition" => 'sgf.id_feature = sf.id'
                ),
                array(
                    "type" => "left",
                    "table" => 'shop_modifications_group smg',
                    "condition" => 'sgf.id_group = smg.id'
                )
            )
        );
    }

    // получить информацию по настройкам
    protected function getSettingsInfo()
    {
        $this->debugging('funct', __FUNCTION__.' '.__LINE__, __CLASS__, '[comment]');
        return $this->getSettingsFetch();
    }

    // получить значения
    private function getValues()
    {
        $this->debugging('funct', __FUNCTION__.' '.__LINE__, __CLASS__, '[comment]');
        $featureValue = new FeatureValue();
        return $featureValue->fetchByIdFeature($this->input["id"]);
    }

    // получить добавленную информацию
    protected function getAddInfo()
    {
        $this->debugging('funct', __FUNCTION__.' '.__LINE__, __CLASS__, '[comment]');
        $result["values"] = $this->getValues();
        return $result;
    }

    // сохранить значения
    private function saveValues()
    {
        $this->debugging('funct', __FUNCTION__.' '.__LINE__, __CLASS__, '[comment]');
        if (!isset($this->input["values"]))
            return;

        try {
            $idFeature = $this->input["id"];
            $values = $this->input["values"];
            $idsStore = "";
            foreach ($values as $value) {
                if ($value["id"] > 0) {
                    if (!empty($idsStore))
                        $idsStore .= ",";
                    $idsStore .= $value["id"];
                    $u = new DB('shop_feature_value_list');
                    $u->setValuesFields($value);
                    $u->save();
                }
            }

            if (!empty($idsStore)) {
                $u = new DB('shop_feature_value_list');
                $u->where("id_feature = {$idFeature} AND NOT (id IN (?))", $idsStore)->deleteList();
            } else {
                $u = new DB('shop_feature_value_list');
                $u->where("id_feature = ?", $idFeature)->deleteList();
            }

            $data = array();
            foreach ($values as $value)
                if (empty($value["id"]) || ($value["id"] <= 0)) {
                    $data[] = array('id_feature' => $idFeature, 'value' => $value["value"], 'color' => $value["color"],
                        'sort' => (int) $value["sort"], 'image' => $value["image"]);
                }
            if (!empty($data))
                DB::insertList('shop_feature_value_list', $data);
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить значения параметра!";
            throw new Exception($this->error);
        }
    }

    // сохранить добавленную информацию
    public function saveAddInfo()
    {
        $this->debugging('funct', __FUNCTION__.' '.__LINE__, __CLASS__, '[comment]');
        $this->saveValues();
        return true;
    }
}
