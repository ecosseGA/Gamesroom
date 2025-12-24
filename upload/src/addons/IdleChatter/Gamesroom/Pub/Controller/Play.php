<?php

namespace IdleChatter\Gamesroom\Pub\Controller;

use XF\Pub\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Play extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        // Check permission
        if (!\XF::visitor()->hasPermission('gamesroom', 'view'))
        {
            return $this->noPermission();
        }
        
        $game = $this->assertGameExists($params->game_id);
        
        // Increment play count
        $game->play_count++;
        $game->save();
        
        // Get related games
        $relatedGames = $this->repository('IdleChatter\Gamesroom:Game')
            ->findActiveGamesForPublic($game->category_id)
            ->where('game_id', '!=', $game->game_id)
            ->limit(6)
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
