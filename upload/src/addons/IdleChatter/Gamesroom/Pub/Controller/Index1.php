<?php

namespace IdleChatter\Gamesroom\Pub\Controller;

use XF\Pub\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Index extends AbstractController
{
    public function actionIndex()
    {
        // Check permission
        if (!\XF::visitor()->hasPermission('gamesroom', 'view'))
        {
            return $this->noPermission();
        }
        
        $input = $this->filter([
            'category_id' => 'uint',
            'page' => 'uint',
            'search' => 'str'
        ]);
        
        $categoryId = $input['category_id'];
        $page = max(1, $input['page'] ?: 1);
        $perPage = 12; // 24 games per page (4x6 grid)
        $searchTerm = $input['search'];
        
        /** @var \IdleChatter\Gamesroom\Repository\Game $gameRepo */
        $gameRepo = $this->repository('IdleChatter\Gamesroom:Game');
        
        /** @var \IdleChatter\Gamesroom\Repository\Category $categoryRepo */
        $categoryRepo = $this->repository('IdleChatter\Gamesroom:Category');
        
        // Get all games with pagination and search
        if ($searchTerm)
        {
            $gameFinder = $gameRepo->searchGames($searchTerm, $categoryId);
        }
        else
        {
            $gameFinder = $gameRepo->findActiveGamesForPublic($categoryId);
        }
        $total = $gameFinder->total();
        
        // XenForo pagination validation
        $this->assertValidPage($page, $perPage, $total, 'gamesroom', $categoryId ? ['category_id' => $categoryId] : []);
        $this->assertCanonicalUrl($this->buildPaginatedLink('gamesroom', null, $page));
        
        $gameFinder->limitByPage($page, $perPage);
        $games = $gameFinder->fetch();
        
        // Get categories
        $categories = $categoryRepo->findCategoriesForList()->where('active', 1)->fetch();
        
        $viewParams = [
            'games' => $games,
            'categories' => $categories,
            'categoryId' => $categoryId,
            'searchTerm' => $searchTerm,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total
        ];
        
        return $this->view('IdleChatter\Gamesroom:Index', 'gamesroom_index', $viewParams);
    }
}
