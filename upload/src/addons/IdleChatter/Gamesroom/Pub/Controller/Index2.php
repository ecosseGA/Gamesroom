<?php

namespace IdleChatter\Gamesroom\Pub\Controller;

use XF\Pub\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Index extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $categoryId = $this->filter('category', 'uint');
        $sort = $this->filter('sort', 'str');
        $searchTerm = $this->filter('search', 'str');
        
        $categoryId = $input['category_id'];
        $page = max(1, $input['page'] ?: 1);
        $perPage = 12; // 24 games per page (4x6 grid)
        $searchTerm = $input['search'];


	// Valid sort options
        $validSorts = ['title_asc', 'title_desc', 'plays', 'recent'];
        if (!in_array($sort, $validSorts)) {
            $sort = 'recent'; // Default sort
        }
        
        // Build query
        $finder = \XF::finder('IdleChatter\Gamesroom:Game')
            ->where('active', 1)
            ->with('Category');
        
        // Filter by category if selected (0 means "All Categories")
        if ($categoryId > 0) {
            $finder->where('category_id', $categoryId);
        }
        
        // Search filter
        if ($searchTerm) {
            $finder->where('title', 'LIKE', $finder->escapeLike($searchTerm, '%?%'));
        }
        
        // Apply sorting
        switch ($sort) {
            case 'title_asc':
                $finder->order('title', 'ASC');
                break;
            case 'title_desc':
                $finder->order('title', 'DESC');
                break;
            case 'plays':
                $finder->order('play_count', 'DESC');
                break;
            case 'recent':
            default:
                $finder->order('create_date', 'DESC');
                break;
        }
        
        $games = $finder->fetch();
        
        // Get categories for filter
        $categories = $this->finder('IdleChatter\Gamesroom:Category')
            ->order('title', 'ASC')
            ->fetch();
        
        // Get featured games (top 3 most played, no featured column needed)
        $featuredGames = \XF::finder('IdleChatter\Gamesroom:Game')
            ->where('active', 1)
            ->with('Category')
            ->order('play_count', 'DESC')
            ->limit(4)
            ->fetch();
        
        $viewParams = [
            'games' => $games,
            'categories' => $categories,
            'featuredGames' => $featuredGames,
            'selectedCategory' => $categoryId,
            'currentSort' => $sort,
            'searchTerm' => $searchTerm
        ];
        
        return $this->view('IdleChatter\Gamesroom:Index', 'gamesroom_index', $viewParams);
    }
    
    public function actionPlay(ParameterBag $params)
    {
        $game = $this->assertGameExists($params->game_id);
        
        // Increment play count
        $game->play_count++;
        $game->last_played = \XF::$time;
        $game->save();
        
        // Get related games (same category, limit 4)
        $relatedGames = \XF::finder('IdleChatter\Gamesroom:Game')
            ->where('active', 1)
            ->where('category_id', $game->category_id)
            ->where('game_id', '!=', $game->game_id)
            ->with('Category')
            ->order('play_count', 'DESC')
            ->limit(4)
            ->fetch();
        
        $viewParams = [
            'game' => $game,
            'relatedGames' => $relatedGames
        ];
        
        return $this->view('IdleChatter\Gamesroom:Play', 'gamesroom_play', $viewParams);
    }
    
    protected function assertGameExists($id, $with = null, $phraseKey = null)
    {
        return $this->assertRecordExists('IdleChatter\Gamesroom:Game', $id, $with, $phraseKey);
    }
}