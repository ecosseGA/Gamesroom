<?php

namespace IdleChatter\Gamesroom\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Game extends AbstractController
{
    public function actionIndex()
{
    $page = $this->filterPage();
    $perPage = 15; // Games per page
    
    $gameFinder = $this->repository('IdleChatter\Gamesroom:Game')
        ->findGamesForList();
    
    $total = $gameFinder->total();
    
    // Validate page number
    $this->assertValidPage($page, $perPage, $total, 'gamesroom/games');
    
    // Apply pagination
    $gameFinder->limitByPage($page, $perPage);
    $games = $gameFinder->fetch();
    
    $viewParams = [
        'games' => $games,
        'page' => $page,
        'perPage' => $perPage,
        'total' => $total
    ];
    
    return $this->view('IdleChatter\Gamesroom:Game\List', 'gamesroom_game_list', $viewParams);
}    
    public function actionQuickAdd()
    {
        $categories = $this->getCategoryRepo()->getCategoryTitlePairs();
        
        $viewParams = [
            'categories' => $categories
        ];
        
        return $this->view('IdleChatter\Gamesroom:Game\QuickAdd', 'gamesroom_game_quick_add', $viewParams);
    }
	
    
    public function actionQuickAddSave()
    {
        $this->assertPostOnly();
        
        $input = $this->filter([
            'title' => 'str',
            'embed_url' => 'str',
            'thumbnail_url' => 'str',
            'category_id' => 'uint',
            'description' => 'str',
            'width' => 'uint',
            'height' => 'uint'
        ]);
        
        if (!$input['embed_url'])
        {
            return $this->error(\XF::phrase('please_enter_valid_url'));
        }
        
        // Auto-detect distributor from URL
        $distributor = 'custom';
        if (stripos($input['embed_url'], 'gamepix') !== false)
        {
            $distributor = 'gamepix';
            if (!$input['width']) $input['width'] = 800;
            if (!$input['height']) $input['height'] = 600;
        }
        elseif (stripos($input['embed_url'], 'gamedistribution') !== false)
        {
            $distributor = 'gamedistribution';
            if (!$input['width']) $input['width'] = 800;
            if (!$input['height']) $input['height'] = 600;
        }
        elseif (stripos($input['embed_url'], 'gamemonetize') !== false || stripos($input['embed_url'], 'crazygames') !== false)
        {
            $distributor = 'gamemonetize';
            if (!$input['width']) $input['width'] = 800;
            if (!$input['height']) $input['height'] = 600;
        }
        
        $input['distributor'] = $distributor;
        
        if (!$input['width']) $input['width'] = 800;
        if (!$input['height']) $input['height'] = 600;
        
        $game = $this->em()->create('IdleChatter\Gamesroom:Game');
        $game->bulkSet($input);
        $game->save();
        
        return $this->redirect($this->buildLink('gamesroom/games'));
    }
    
    public function actionAdd()
    {
        $game = $this->em()->create('IdleChatter\Gamesroom:Game');
        return $this->gameAddEdit($game);
    }
    
    public function actionEdit(ParameterBag $params)
    {
        $game = $this->assertGameExists($params->game_id);
        return $this->gameAddEdit($game);
    }
    
    protected function gameAddEdit(\IdleChatter\Gamesroom\Entity\Game $game)
    {
        $categories = $this->getCategoryRepo()->getCategoryTitlePairs();
        $categories = [0 => '(No category)'] + $categories;
        
        $viewParams = [
            'game' => $game,
            'categories' => $categories
        ];
        
        return $this->view('IdleChatter\Gamesroom:Game\Edit', 'gamesroom_game_edit', $viewParams);
    }
    
    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        
        if ($params->game_id)
        {
            $game = $this->assertGameExists($params->game_id);
        }
        else
        {
            $game = $this->em()->create('IdleChatter\Gamesroom:Game');
        }
        
        $this->gameSaveProcess($game)->run();
        
        return $this->redirect($this->buildLink('gamesroom/games'));
    }
    
    protected function gameSaveProcess(\IdleChatter\Gamesroom\Entity\Game $game)
    {
        $form = $this->formAction();
        
        $input = $this->filter([
            'category_id' => 'uint',
            'title' => 'str',
            'description' => 'str',
            'embed_url' => 'str',
            'thumbnail_url' => 'str',
            'distributor' => 'str',
            'width' => 'uint',
            'height' => 'uint',
            'display_order' => 'uint',
            'active' => 'bool'
        ]);
        
        $form->basicEntitySave($game, $input);
        
        return $form;
    }
    
    public function actionDelete(ParameterBag $params)
    {
        $game = $this->assertGameExists($params->game_id);
        
        if ($this->isPost())
        {
            $game->delete();
            return $this->redirect($this->buildLink('gamesroom/games'));
        }
        else
        {
            $viewParams = [
                'game' => $game
            ];
            
            return $this->view('IdleChatter\Gamesroom:Game\Delete', 'gamesroom_game_delete', $viewParams);
        }
    }
    
    protected function assertGameExists($id, $with = null, $phraseKey = null)
    {
        return $this->assertRecordExists('IdleChatter\Gamesroom:Game', $id, $with, $phraseKey);
    }
    
    protected function getGameRepo()
    {
        return $this->repository('IdleChatter\Gamesroom:Game');
    }
    
    protected function getCategoryRepo()
    {
        return $this->repository('IdleChatter\Gamesroom:Category');
    }
}
