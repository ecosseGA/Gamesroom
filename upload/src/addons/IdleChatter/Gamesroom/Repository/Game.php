<?php

namespace IdleChatter\Gamesroom\Repository;

use XF\Mvc\Entity\Repository;

class Game extends Repository
{
    public function findGamesForList($categoryId = null)
    {
        $finder = $this->finder('IdleChatter\Gamesroom:Game')
            ->with('Category')
            ->order('display_order');
        
        if ($categoryId)
        {
            $finder->where('category_id', $categoryId);
        }
        
        return $finder;
    }
    
    public function findActiveGamesForPublic($categoryId = null)
    {
        $finder = $this->finder('IdleChatter\Gamesroom:Game')
            ->with('Category')
            ->where('active', 1)
            ->order('display_order');
        
        if ($categoryId)
        {
            $finder->where('category_id', $categoryId);
        }
        
        return $finder;
    }
    
    public function getFeaturedGames($limit = 10)
    {
        // Return most played games since featured column doesn't exist
        return $this->finder('IdleChatter\Gamesroom:Game')
            ->where('active', 1)
            ->order('play_count', 'DESC')
            ->limit($limit)
            ->fetch();
    }
    
    public function getMostPlayedGames($limit = 10)
    {
        return $this->finder('IdleChatter\Gamesroom:Game')
            ->where('active', 1)
            ->order('play_count', 'DESC')
            ->limit($limit)
            ->fetch();
    }
    
    public function searchGames($searchTerm, $categoryId = null)
    {
        $finder = $this->finder('IdleChatter\Gamesroom:Game')
            ->where('active', 1);
        
        if ($searchTerm)
        {
            // No need for whereSql now - title is unambiguous without Category join
            $finder->where('title', 'LIKE', $finder->escapeLike($searchTerm, '%?%'));
        }
        
        if ($categoryId)
        {
            $finder->where('category_id', $categoryId);
        }
        
        $finder->order('play_count', 'DESC');
        
        return $finder;
    }
    
    public function getRecentGames($limit = 10)
    {
        return $this->finder('IdleChatter\Gamesroom:Game')
            ->where('active', 1)
            ->order('create_date', 'DESC')
            ->limit($limit)
            ->fetch();
    }
    
    public function getLatestPlayedGames($limit = 5)
    {
        // Return most played games since last_played column doesn't exist
        return $this->finder('IdleChatter\Gamesroom:Game')
            ->where('active', 1)
            ->where('play_count', '>', 0)
            ->order('play_count', 'DESC')
            ->limit($limit)
            ->fetch();
    }
}