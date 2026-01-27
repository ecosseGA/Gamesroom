<?php

namespace IdleChatter\Gamesroom\Widget;

use XF\Widget\AbstractWidget;

class GamesroomStats extends AbstractWidget
{
    protected $defaultOptions = [];

    public function render()
    {
        $db = \XF::db();
        
        // ========== PUBLIC STATS ==========
        
        // Total games
        $totalGames = $db->fetchOne("
            SELECT COUNT(*) 
            FROM xf_gamesroom_game 
            WHERE active = 1
        ");
        
        // Total plays across all games
        $totalPlays = $db->fetchOne("
            SELECT SUM(play_count) 
            FROM xf_gamesroom_game
        ");
        
        // Most popular game
        $topGame = $db->fetchRow("
            SELECT game_id, title, play_count 
            FROM xf_gamesroom_game 
            WHERE active = 1 
            ORDER BY play_count DESC 
            LIMIT 1
        ");
        
        // Recently added (last 7 days)
        $recentCount = $db->fetchOne("
            SELECT COUNT(*) 
            FROM xf_gamesroom_game 
            WHERE active = 1 
            AND create_date >= ?
        ", \XF::$time - (7 * 86400));
        
        // Top 5 games by plays
        $topGames = $db->fetchAll("
            SELECT game_id, title, play_count 
            FROM xf_gamesroom_game 
            WHERE active = 1 
            ORDER BY play_count DESC 
            LIMIT 5
        ");
        
        // Total categories
        $totalCategories = $db->fetchOne("
            SELECT COUNT(*) 
            FROM xf_gamesroom_category
        ");
        
        // Most popular category
        $topCategory = $db->fetchRow("
            SELECT c.category_id, c.title, SUM(g.play_count) as total_plays
            FROM xf_gamesroom_category c
            INNER JOIN xf_gamesroom_game g ON g.category_id = c.category_id
            WHERE g.active = 1 AND c.category_id > 0
            GROUP BY c.category_id
            ORDER BY total_plays DESC
            LIMIT 1
        ");
        
        $viewParams = [
            'totalGames' => $totalGames,
            'totalPlays' => $totalPlays ?? 0,
            'topGame' => $topGame,
            'topGames' => $topGames,
            'recentCount' => $recentCount,
            'totalCategories' => $totalCategories,
            'topCategory' => $topCategory
        ];
        
        return $this->renderer('gamesroom_widget_stats', $viewParams);
    }
}
