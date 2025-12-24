<?php

namespace IdleChatter\Gamesroom\Repository;

use XF\Mvc\Entity\Repository;

class Category extends Repository
{
    public function findCategoriesForList()
    {
        return $this->finder('IdleChatter\Gamesroom:Category')
            ->order('display_order')
            ->order('title');
    }
    
    public function getActiveCategoriesWithGames()
    {
        return $this->finder('IdleChatter\Gamesroom:Category')
            ->where('active', true)
            ->where('game_count', '>', 0)
            ->order('display_order')
            ->fetch();
    }
    
    public function getCategoryTitlePairs()
    {
        return $this->finder('IdleChatter\Gamesroom:Category')
            ->where('active', true)
            ->order('display_order')
            ->fetch()
            ->pluckNamed('title', 'category_id');
    }
}
