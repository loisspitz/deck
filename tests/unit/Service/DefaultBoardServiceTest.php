<?php
/**
 * @copyright Copyright (c) 2018 Ryan Fletcher <ryan.fletcher@codepassion.ca>
 *
 * @author Ryan Fletcher <ryan.fletcher@codepassion.ca>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Deck\Service;

use OCA\Deck\Db\Card;
use OCA\Deck\Db\Board;
use OCA\Deck\Db\Stack;
use OCA\Deck\Db\BoardMapper;
use OCA\Deck\Service\DefaultBoardService;
use OCA\Deck\Service\BoardService;
use OCA\Deck\Service\StackService;
use OCA\Deck\Service\CardService;
use OCP\IConfig;
use \Test\TestCase;

class DefaultBoardServiceTest extends TestCase {
	
	/** @var DefaultBoardService */
	private $service;	

	/** @var BoardService */
	private $boardService;

	/** @var StackService */
	private $stackService;

	/** @var CardService */
	private $cardService;

	/** @var BoardMapper */
	private $boardMapper;	

	/** @var IConfig */
	private $config;
	
	private $userId = 'admin';	

	public function setUp() {
		parent::setUp();
		$this->boardMapper = $this->createMock(BoardMapper::class);
		$this->boardService = $this->createMock(BoardService::class);
		$this->stackService = $this->createMock(StackService::class);
		$this->cardService = $this->createMock(CardService::class);
		$this->config = $this->createMock(IConfig::class);

		$this->service = new DefaultBoardService(
			$this->boardMapper,
			$this->boardService,
			$this->stackService,
			$this->cardService,
			$this->config
		);		
	}

	public function testCreateDefaultBoard() {	
		$title = 'Personal';		
		$color = '000000';
		$boardId = 5;
		
		$board = new Board();
		$board->setId($boardId);
		$board->setTitle($title);
		$board->setOwner($this->userId);
		$board->setColor($color);
		$this->boardService->expects($this->once())
			 ->method('create')
			 ->willReturn($board);

		$stackToDoId = '123';		
		$stackToDo = $this->assembleTestStack('To do', $stackToDoId, $boardId);		
		
		$stackDoingId = '124';		
		$stackDoing = $this->assembleTestStack('Doing', $stackDoingId, $boardId);		
		
		$stackDoneId = '125';
		$stackDone = $this->assembleTestStack('Done', $stackDoneId, $boardId);
		$this->stackService->expects($this->exactly(3))
			->method('create')
			->withConsecutive(
				['To do', $boardId, 1],
				['Doing', $boardId, 1],
				['Done',  $boardId, 1]
			)
			->willReturnOnConsecutiveCalls($stackToDo, $stackDoing, $stackDone);
		
		$cardExampleTask3 = $this->assembleTestCard('Example Task 3', $stackToDoId, $this->userId);
		$cardExampleTask2 = $this->assembleTestCard('Example Task 2', $stackDoingId, $this->userId);
		$cardExampleTask1 = $this->assembleTestCard('Example Task 1', $stackDoneId, $this->userId);
		$this->cardService->expects($this->exactly(3))
			->method('create')
			->withConsecutive(
				['Example Task 3', $stackToDoId, 'text', 0, $this->userId],
				['Example Task 2', $stackDoingId, 'text', 0, $this->userId],
				['Example Task 1', $stackDoneId, 'text', 0, $this->userId]
			)
			->willReturnonConsecutiveCalls($cardExampleTask3, $cardExampleTask2, $cardExampleTask1);

		$result = $this->service->createDefaultBoard($title, $this->userId, $color);

		$this->assertEquals($result->getTitle(), $title);
		$this->assertEquals($result->getOwner(), $this->userId);
		$this->assertEquals($result->getColor(), $color);
	}
	
	private function assembleTestStack($title, $id, $boardId) {		
		$stack = new Stack();
		$stack->setId($id);
		$stack->setTitle($title);
		$stack->setBoardId($boardId);
		$stack->setOrder(1);

		return $stack;
	}

	private function assembleTestCard($title, $stackId, $userId) {
		$card = new Card();
		$card->setTitle($title);
		$card->setStackId($stackId);
		$card->setType('text');
		$card->setOrder(0);
		$card->setOwner($userId);

		return $card;
	}
}