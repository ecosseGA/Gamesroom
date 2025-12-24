<?php

namespace IdleChatter\Gamesroom\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;
use IdleChatter\Gamesroom\Service\GameCatalog;

class Browse extends AbstractController
{
    public function actionIndex()
    {
        $input = $this->filter([
            'page' => 'uint',
            'category' => 'str',
            'search' => 'str'
        ]);
        
        $page = max(1, $input['page'] ?: 1);
        $perPage = 50;
        
        // Get games from GameMonetize catalog
        $result = GameCatalog::getGames('gamemonetize', $page, $perPage, $input['category'], $input['search']);
        
        // XenForo pagination validation
        $total = $result['total'];
        $this->assertValidPage($page, $perPage, $total, 'gamesroom/browse');
        
        // Get imported game URLs
        $importedUrls = $this->getImportedGameUrls();
        
        // Mark games as imported
        foreach ($result['games'] as &$game) {
            $game['is_imported'] = isset($importedUrls[$game['url']]);
        }
        
        // Get our categories for import
        $categoryRepo = $this->repository('IdleChatter\Gamesroom:Category');
        $categories = $categoryRepo->getCategoryTitlePairs();
        
        $viewParams = [
            'games' => $result['games'],
            'categories' => $categories,
            'apiCategories' => GameCatalog::getCategories(),
            'selectedCategory' => $input['category'],
            'searchTerm' => $input['search'],
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'hasMore' => $result['hasMore']
        ];
        
        return $this->view('IdleChatter\Gamesroom:Browse\Index', 'gamesroom_browse_catalog', $viewParams);
    }
    
    public function actionImport()
    {
        $this->assertPostOnly();
        
        $input = $this->filter([
            'game_url' => 'str',
            'game_title' => 'str',
            'game_description' => 'str',
            'game_thumbnail' => 'str',
            'game_width' => 'uint',
            'game_height' => 'uint',
            'category_id' => 'uint',
            'featured' => 'bool'
        ]);
        
        if (!$input['game_url'] || !$input['game_title']) {
            return $this->error(\XF::phrase('please_enter_valid_value'));
        }
        
        // Check if already imported
        $existing = $this->em()->findOne('IdleChatter\Gamesroom:Game', ['embed_url' => $input['game_url']]);
        if ($existing) {
            return $this->error('This game has already been imported.');
        }
        
        /** @var \IdleChatter\Gamesroom\Entity\Game $game */
        $game = $this->em()->create('IdleChatter\Gamesroom:Game');
        $game->title = $input['game_title'];
        $game->description = $input['game_description'];
        $game->embed_url = $input['game_url'];
        $game->thumbnail_url = $input['game_thumbnail'];
        $game->category_id = $input['category_id'] ?: 0;
        $game->distributor = 'gamemonetize';
        $game->width = $input['game_width'] ?: 800;
        $game->height = $input['game_height'] ?: 600;
        $game->active = true;
        $game->save();
        
        return $this->redirect($this->buildLink('gamesroom/browse'));
    }
    
    protected function getImportedGameUrls()
    {
        $games = $this->em()->getFinder('IdleChatter\Gamesroom:Game')
            ->fetch();
        
        $urls = [];
        foreach ($games as $game) {
            $urls[$game->embed_url] = true;
        }
        
        return $urls;
    }
}
