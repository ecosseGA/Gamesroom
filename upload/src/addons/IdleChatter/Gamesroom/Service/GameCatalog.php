<?php

namespace IdleChatter\Gamesroom\Service;

/**
 * GameMonetize catalog only - fetches from RSS feed
 */
class GameCatalog
{
    public static function getGames($distributor = 'gamemonetize', $page = 1, $limit = 50, $category = null, $search = null)
    {
        $allGames = self::getGameMonetizeGames();
        
        // Filter by category
        if ($category && $category !== 'All') {
            $allGames = array_filter($allGames, function($game) use ($category) {
                $gameCategory = isset($game['category']) ? strtolower($game['category']) : '';
                return strpos($gameCategory, strtolower($category)) !== false;
            });
        }
        
        // Filter by search
        if ($search) {
            $searchLower = strtolower($search);
            $allGames = array_filter($allGames, function($game) use ($searchLower) {
                return strpos(strtolower($game['title']), $searchLower) !== false ||
                       strpos(strtolower($game['description']), $searchLower) !== false;
            });
        }
        
        // Re-index
        $allGames = array_values($allGames);
        
        // Pagination
        $total = count($allGames);
        $offset = ($page - 1) * $limit;
        $games = array_slice($allGames, $offset, $limit);
        
        return [
            'games' => $games,
            'total' => $total,
            'page' => $page,
            'hasMore' => ($offset + $limit) < $total
        ];
    }
    
    public static function getCategories($distributor = 'gamemonetize')
    {
        return [
            'puzzle' => 'Puzzle',
            'arcade' => 'Arcade',
            'action' => 'Action',
            'sports' => 'Sports',
            'racing' => 'Racing',
            'strategy' => 'Strategy',
            'cards' => 'Cards',
            'adventure' => 'Adventure'
        ];
    }
    
    /**
     * Fetch MAXIMUM GameMonetize games from RSS feed
     */
    protected static function getGameMonetizeGames()
    {
        // Fetch ALL games from GameMonetize RSS (no limit)
        $feedUrl = 'https://rss.gamemonetize.com/rssfeed.php?format=json&category=All&type=html5&popularity=newest&company=All&amount=All';
        
        $games = [];
        
        // Use curl for better reliability
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $feedUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Large feed needs time
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            
            $jsonData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode == 200 && $jsonData && !$error) {
                $data = json_decode($jsonData, true);
                
                if ($data && is_array($data)) {
                    foreach ($data as $game) {
                        // Only include games with valid data
                        if (isset($game['title']) && isset($game['url']) && isset($game['thumb'])) {
                            $games[] = [
                                'title' => $game['title'],
                                'description' => $game['description'] ?? 'Play this HTML5 game',
                                'thumbnail' => $game['thumb'],
                                'url' => $game['url'],
                                'category' => $game['category'] ?? 'Arcade',
                                'distributor' => 'gamemonetize',
                                'width' => $game['width'] ?? 800,
                                'height' => $game['height'] ?? 600
                            ];
                        }
                    }
                }
            }
        }
        // Fallback to file_get_contents if curl not available
        else {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 60,
                    'user_agent' => 'Mozilla/5.0'
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $jsonData = @file_get_contents($feedUrl, false, $context);
            
            if ($jsonData) {
                $data = json_decode($jsonData, true);
                
                if ($data && is_array($data)) {
                    foreach ($data as $game) {
                        if (isset($game['title']) && isset($game['url']) && isset($game['thumb'])) {
                            $games[] = [
                                'title' => $game['title'],
                                'description' => $game['description'] ?? 'Play this HTML5 game',
                                'thumbnail' => $game['thumb'],
                                'url' => $game['url'],
                                'category' => $game['category'] ?? 'Arcade',
                                'distributor' => 'gamemonetize',
                                'width' => $game['width'] ?? 800,
                                'height' => $game['height'] ?? 600
                            ];
                        }
                    }
                }
            }
        }
        
        return $games;
    }
}
