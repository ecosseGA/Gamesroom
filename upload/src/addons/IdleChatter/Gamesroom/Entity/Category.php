<?php

namespace IdleChatter\Gamesroom\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Category extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_gamesroom_category';
        $structure->shortName = 'IdleChatter\Gamesroom:Category';
        $structure->primaryKey = 'category_id';
        $structure->columns = [
            'category_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'title' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'description' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'icon' => ['type' => self::STR, 'maxLength' => 50, 'default' => 'fa-gamepad'],
            'display_order' => ['type' => self::UINT, 'default' => 0],
            'active' => ['type' => self::BOOL, 'default' => true],
            'game_count' => ['type' => self::UINT, 'default' => 0]
        ];
        
        $structure->relations = [
            'Games' => [
                'entity' => 'IdleChatter\Gamesroom:Game',
                'type' => self::TO_MANY,
                'conditions' => 'category_id',
                'order' => 'display_order'
            ]
        ];
        
        return $structure;
    }
    
    protected function _postDelete()
    {
        // Move games in this category to uncategorized (category_id = 0)
        $this->db()->update('xf_gamesroom_game', 
            ['category_id' => 0], 
            'category_id = ?', 
            $this->category_id
        );
    }
}
