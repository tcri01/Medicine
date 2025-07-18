<?php

namespace App\Enums\Medicine;

enum AppearanceKeyEnum: string
{
    case NOTCH = "notch";
    case MARK_ONE = "mark_one";
    case COLOR = "color";
    case IMAGE_LINK = "image_link";
    case MARK_TWO = "mark_two";
    case SHAPE = "shape";
    case SIZE = "size";
    case CHINESE_NAME = "chinese_name";
    case ENGLISH_NAME = "english_name";
    case LICENSE_NUMBER = "license_number";
    case SPECIAL_SMELL = "special_smell";
    case SPECIAL_DOSAGE_FORM = "special_dosage_form";

    public function description(): string
    {
        return match ($this) {
            self::NOTCH => '刻痕',
            self::MARK_ONE => '標註一',
            self::COLOR => '顏色',
            self::IMAGE_LINK => '外觀圖檔連結',
            self::MARK_TWO => '標註二',
            self::SHAPE => '形狀',
            self::SIZE => '外觀尺寸',
            self::CHINESE_NAME => '中文品名',
            self::ENGLISH_NAME => '英文品名',
            self::LICENSE_NUMBER => '許可證字號',
            self::SPECIAL_SMELL => '特殊氣味',
            self::SPECIAL_DOSAGE_FORM => '特殊劑型',
        };
    }

    public static function translationMap(): array
    {
        return [
            '刻痕' => self::NOTCH->value,
            '標註一' => self::MARK_ONE->value,
            '顏色' => self::COLOR->value,
            '外觀圖檔連結' => self::IMAGE_LINK->value,
            '標註二' => self::MARK_TWO->value,
            '形狀' => self::SHAPE->value,
            '外觀尺寸' => self::SIZE->value,
            '中文品名' => self::CHINESE_NAME->value,
            '英文品名' => self::ENGLISH_NAME->value,
            '許可證字號' => self::LICENSE_NUMBER->value,
            '特殊氣味' => self::SPECIAL_SMELL->value,
            '特殊劑型' => self::SPECIAL_DOSAGE_FORM->value,
        ];
    }
}
