<?php

namespace IdleChatter\Gamesroom\Widget;

use XF\Widget\AbstractWidget;

class LatestPlayed extends AbstractWidget
{
	protected $defaultOptions = [
		'limit' => 5
	];

	public function render()
	{
		$options = $this->options;
		$limit = $options['limit'];

		/** @var \IdleChatter\Gamesroom\Repository\Game $gameRepo */
		$gameRepo = $this->repository('IdleChatter\Gamesroom:Game');
		
		$games = $gameRepo->getLatestPlayedGames($limit);

		if (!$games->count())
		{
			return '';
		}

		$viewParams = [
			'title' => $this->getTitle() ?: \XF::phrase('gamesroom_latest_played'),
			'games' => $games
		];
		
		return $this->renderer('gamesroom_widget_latest_played', $viewParams);
	}

	public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null)
	{
		$options = $request->filter([
			'limit' => 'uint'
		]);
		
		if ($options['limit'] < 1)
		{
			$options['limit'] = 1;
		}
		
		if ($options['limit'] > 20)
		{
			$options['limit'] = 20;
		}

		return true;
	}
}
