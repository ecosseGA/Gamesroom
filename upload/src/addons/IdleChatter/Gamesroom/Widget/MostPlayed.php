<?php

namespace IdleChatter\Gamesroom\Widget;

use XF\Widget\AbstractWidget;

class MostPlayed extends AbstractWidget
{
	protected $defaultOptions = [
		'limit' => 8
	];

	public function render()
	{
		$options = $this->options;
		$limit = $options['limit'];

		/** @var \IdleChatter\Gamesroom\Repository\Game $gameRepo */
		$gameRepo = $this->repository('IdleChatter\Gamesroom:Game');
		
		$games = $gameRepo->getMostPlayedGames($limit);

		if (!$games->count())
		{
			return '';
		}

		$viewParams = [
			'title' => $this->getTitle() ?: \XF::phrase('gamesroom_most_played'),
			'games' => $games
		];
		
		return $this->renderer('gamesroom_widget_most_played', $viewParams);
	}

	public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null)
	{
		$options = $request->filter([
			'limit' => 'uint'
		]);
		
		if ($options['limit'] < 1)
		{
			$options['limit'] = 5;
		}
		
		if ($options['limit'] > 20)
		{
			$options['limit'] = 20;
		}

		return true;
	}
}
