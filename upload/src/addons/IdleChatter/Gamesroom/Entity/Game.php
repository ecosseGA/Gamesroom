<?php

namespace IdleChatter\Gamesroom\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $game_id
 * @property int $category_id
 * @property string $title
 * @property string $description
 * @property string $embed_url
 * @property string $thumbnail_url
 * @property string $distributor
 * @property int $width
 * @property int $height
 * @property int $display_order
 * @property bool $active
 * @property int $play_count
 * @property int $create_date
 * @property int $update_date
 *
 * RELATIONS
 * @property \IdleChatter\Gamesroom\Entity\Category $Category
 */
class Game extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_gamesroom_game';
        $structure->shortName = 'IdleChatter\Gamesroom:Game';
        $structure->primaryKey = 'game_id';
        
        $structure->columns = [
            'game_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'category_id' => ['type' => self::UINT, 'default' => 0],
            'title' => ['type' => self::STR, 'maxLength' => 200, 'required' => true],
            'description' => ['type' => self::STR, 'maxLength' => 2000, 'default' => ''],
            'embed_url' => ['type' => self::STR, 'maxLength' => 500, 'required' => true],
            'thumbnail_url' => ['type' => self::STR, 'maxLength' => 500, 'default' => ''],
            'distributor' => ['type' => self::STR, 'maxLength' => 50, 'default' => 'custom',
                'allowedValues' => ['gamepix', 'gamedistribution', 'gamemonetize', 'custom']
            ],
            'width' => ['type' => self::UINT, 'default' => 800],
            'height' => ['type' => self::UINT, 'default' => 600],
            'display_order' => ['type' => self::UINT, 'default' => 10],
            'active' => ['type' => self::BOOL, 'default' => true],
            'play_count' => ['type' => self::UINT, 'default' => 0],
            'create_date' => ['type' => self::UINT, 'default' => \XF::$time],
            'update_date' => ['type' => self::UINT, 'default' => \XF::$time]
        ];
        
        $structure->relations = [
            'Category' => [
                'entity' => 'IdleChatter\Gamesroom:Category',
                'type' => self::TO_ONE,
                'conditions' => 'category_id',
                'primary' => true
            ]
        ];
        
        return $structure;
    }
    
    public function getDistributorName()
    {
        $names = [
            'gamepix' => 'GamePix',
            'gamedistribution' => 'GameDistribution',
            'gamemonetize' => 'GameMonetize',
            'custom' => 'Custom'
        ];
        return $names[$this->distributor] ?? 'Custom';
    }
    
    protected function _preSave()
    {
        if ($this->isChanged('category_id'))
        {
            $this->updateCategoryGameCount();
        }
        
        if ($this->isUpdate())
        {
            $this->update_date = \XF::$time;
        }
    }
    
    protected function _postSave()
    {
        $this->rebuildCategoryGameCount();
    }
    
    protected function _postDelete()
    {
        $this->rebuildCategoryGameCount();
    }
    
    protected function updateCategoryGameCount()
    {
        if ($this->isUpdate() && $this->isChanged('category_id'))
        {
            $oldCategoryId = $this->getExistingValue('category_id');
            if ($oldCategoryId)
            {
                $this->rebuildCategoryGameCount($oldCategoryId);
            }
        }
    }
    
    protected function rebuildCategoryGameCount($categoryId = null)
    {
        $categoryId = $categoryId ?? $this->category_id;
        
        if (!$categoryId)
        {
            return;
        }
        
        $count = $this->db()->fetchOne("
            SELECT COUNT(*)
            FROM xf_gamesroom_game
            WHERE category_id = ? AND active = 1
        ", $categoryId);
        
        $this->db()->update('xf_gamesroom_category',
            ['game_count' => $count],
            'category_id = ?',
            $categoryId
        );
    }
    
    public function logPlay()
    {
        $this->play_count++;
        $this->update_date = \XF::$time;
        $this->saveIfChanged();
    }
}
