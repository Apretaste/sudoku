<?php

use Apretaste\Challenges;
use Apretaste\Level;
use Apretaste\Request;
use Apretaste\Response;
use Apretaste\Game;

class Service
{
	/**
	 * Function executed when the service is called
	 *
	 */
	public function _main(Request $request, Response &$response)
	{
		$sudoku = [];
		for ($i = 0; $i < 81; $i++) {
			$sudoku[] = 0;
		}

		$this->solve($sudoku);
		$original = $sudoku;
		$solution = $sudoku;

		$hide = mt_rand(40, 60);

		for ($i = 0; $i < $hide; $i++) {
			$p = mt_rand(0, 80);
			$sudoku[$p] = 0;
			$solution[$p] = $solution[$p] * -1;
		}

		$htmlproblem = $this->print_sudoku($sudoku);
		$forprint = $this->print_sudoku($sudoku, true);

		// hash del board
		$hash = sha1(serialize([$sudoku, $original]));

		// ver si tiene alguna partida abierta con este board
		$openMatch = Game::getOpenMatch('sudoku', $request->person->id, $hash);

		if ($openMatch === null) {
			// si no la tiene, registrar la partida
			$matchId = Game::registerMatch('sudoku', $hash);

			// agregarlo como participante
			Game::addParticipant($matchId, $request->person->id);
		} else {
			$matchId = $openMatch->id;
		}

		// create response
		$responseContent = [
			'matchId' => $matchId,
			'sudoku' => $sudoku,
			'original' => $original,
			'problem' => $htmlproblem,
			'problem_print' => $forprint
		];

		$response->setTemplate('basic.ejs', $responseContent);
	}

	/**
	 * SOLVE subservice
	 *
	 * @param \Apretaste\Request $request
	 * @param \Apretaste\Response $response
	 */
	public function _solve(Request $request, Response &$response)
	{
		$matchId = $request->input->data->matchId ?? null;
		$personId = $request->person->id;

		if ($matchId !== null) {
			// si es participante y si no ha ganado antes esa partida
			if (Game::checkParticipant($matchId, $personId) && !Game::checkWinner($matchId, $personId)) {
				Challenges::complete("complete-sudoku", $personId);
				Level::setExperience('WIN_SUDOKU', $personId);
				Game::finishMatch($matchId, [$personId]);
			}
		}

	}

	/**
	 * ROW of cell
	 *
	 * @param $cell
	 *
	 * @return float
	 */
	private function return_row($cell)
	{
		return floor($cell / 9);
	}

	/**
	 * COL of cell
	 *
	 * @param $cell
	 *
	 * @return int
	 */
	private function return_col($cell)
	{
		return $cell % 9;
	}

	/**
	 * Block of cell
	 *
	 * @param $cell
	 *
	 * @return float|int
	 */
	private function return_block($cell)
	{
		return floor($this->return_row($cell) / 3) * 3 + floor($this->return_col($cell) / 3);
	}

	/**
	 * Is posssible row
	 */
	private function is_possible_row($number, $row, $sudoku)
	{
		$possible = true;
		for ($x = 0; $x <= 8; $x++) {
			if ($sudoku[$row * 9 + $x] == $number) {
				$possible = false;
			}
		}

		return $possible;
	}

	/**
	 * Is posible column?
	 *
	 * @param $number
	 * @param $col
	 * @param $sudoku
	 *
	 * @return bool
	 */
	private function is_possible_col($number, $col, $sudoku)
	{
		$possible = true;
		for ($x = 0; $x <= 8; $x++) {
			if ($sudoku[$col + 9 * $x] == $number) {
				$possible = false;
			}
		}

		return $possible;
	}

	/**
	 * Is possible block?
	 *
	 * @param $number
	 * @param $block
	 * @param $sudoku
	 *
	 * @return bool
	 */
	public function is_possible_block($number, $block, $sudoku)
	{
		$possible = true;
		for ($x = 0; $x <= 8; $x++) {
			if ($sudoku[floor($block / 3) * 27 + $x % 3 + 9 * floor($x / 3) + 3 * ($block % 3)] == $number) {
				$possible = false;
			}
		}

		return $possible;
	}

	/**
	 * Is possible number?
	 *
	 * @param $cell
	 * @param $number
	 * @param $sudoku
	 *
	 * @return bool
	 */
	private function is_possible_number($cell, $number, $sudoku)
	{
		$row = $this->return_row($cell);
		$col = $this->return_col($cell);
		$block = $this->return_block($cell);

		return $this->is_possible_row($number, $row, $sudoku) and $this->is_possible_col($number, $col, $sudoku) and $this->is_possible_block($number, $block, $sudoku);
	}

	/**
	 * Print sudoku
	 *
	 * @param $sudoku
	 * @param bool $for_print
	 *
	 * @return string
	 */
	private function print_sudoku($sudoku, $for_print = false)
	{
		$html = "<table align = \"center\" cellspacing = \"1\" cellpadding = \"2\">\n";

		$squares = [
			1, 1, 1, 2, 2, 2, 3, 3, 3,
			1, 1, 1, 2, 2, 2, 3, 3, 3,
			1, 1, 1, 2, 2, 2, 3, 3, 3,
			4, 4, 4, 5, 5, 5, 6, 6, 6,
			4, 4, 4, 5, 5, 5, 6, 6, 6,
			4, 4, 4, 5, 5, 5, 6, 6, 6,
			7, 7, 7, 8, 8, 8, 9, 9, 9,
			7, 7, 7, 8, 8, 8, 9, 9, 9,
			7, 7, 7, 8, 8, 8, 9, 9, 9
		];

		for ($x = 0; $x <= 8; $x++) {
			$html .= "<tr align = \"center\">\n";
			for ($y = 0; $y <= 8; $y++) {
				$i = ($x * 9 + $y);
				$style = 'border-right: 1px solid gray;';

				if (($x + 1) % 3 == 0) {
					$style .= 'border-bottom: 3px solid black;';
				} else {
					$style .= 'border-bottom: 1px solid gray;';
				}

				if (($y + 1) % 3 == 0) {
					$style .= 'border-right: 3px solid black;';
				}
				if ($x == 0) {
					$style .= 'border-top: 3px solid black;';
				}
				if ($y == 0) {
					$style .= 'border-left: 3px solid black;';
				}

				$v = $sudoku[$i];

				if ($v < 0) {
					$v = 0 - $v;
				} elseif ($v == 0) {
					$v = '&nbsp;';
				}

				$classes = "";
				if ($v <= 0) {
					$classes = "sudoku-cell sudoku-hole";
				} else {
					$classes = "sudoku-cell sudoku-rock";
				}

				$sq = $squares[$i];
				$html .= "<td id=\"sudoku-cell-$i\" class=\"$classes row-$x col-$y square-$sq\" data-row=\"$x\" data-col=\"$y\" data-square=\"$sq\" style=\"$style\" data-i=\"$i\">";

				if ($v == '&nbsp;') {
					/*if ($for_print) {
						$html .= '&nbsp;';
					} else {*/
					$html .= '&nbsp;';
					/*$html .= '<select style="padding: 3px; border: none; background:white;"><option value="-">&nbsp;</option>';
					for ($i = 1; $i <= 9; $i++) {
						$html .= '<option value="'.$i.'">'.$i.'</option>';
					}
					$html .= '</select>';*/
					// }
				} else {
					$html .= $v;
				}

				$html .= "</td>\n";
			}
			$html .= "</tr>\n";
		}
		$html .= "</table>\n";

		return $html;
	}

	/**
	 * Is correct row?
	 *
	 * @param $row
	 * @param $sudoku
	 *
	 * @return bool
	 */
	private function is_correct_row($row, $sudoku)
	{
		for ($x = 0; $x <= 8; $x++) {
			$row_temp[$x] = $sudoku[$row * 9 + $x];
		}

		return count(array_diff([1, 2, 3, 4, 5, 6, 7, 8, 9], $row_temp)) == 0;
	}

	/**
	 * Is correct column?
	 *
	 * @param $col
	 * @param $sudoku
	 *
	 * @return bool
	 */
	private function is_correct_col($col, $sudoku)
	{
		for ($x = 0; $x <= 8; $x++) {
			$col_temp[$x] = $sudoku[$col + $x * 9];
		}

		return count(array_diff([1, 2, 3, 4, 5, 6, 7, 8, 9], $col_temp)) == 0;
	}

	/**
	 * Is correct block?
	 *
	 * @param $block
	 * @param $sudoku
	 *
	 * @return bool
	 */
	private function is_correct_block($block, $sudoku)
	{
		for ($x = 0; $x <= 8; $x++) {
			$block_temp[$x] = $sudoku[floor($block / 3) * 27 + $x % 3 + 9 * floor($x / 3) + 3 * ($block % 3)];
		}

		return count(array_diff([1, 2, 3, 4, 5, 6, 7, 8, 9], $block_temp)) == 0;
	}

	/**
	 * Is sudoku solved?
	 *
	 * @param $sudoku
	 *
	 * @return bool
	 */
	private function is_solved_sudoku($sudoku)
	{
		for ($x = 0; $x <= 8; $x++) {
			if (!$this->is_correct_block($x, $sudoku) or !$this->is_correct_row($x, $sudoku) or !$this->is_correct_col($x, $sudoku)) {
				return false;
				break;
			}
		}

		return true;
	}

	/**
	 * Calculate possible values for cell
	 *
	 * @param $cell
	 * @param $sudoku
	 *
	 * @return array
	 */
	private function determine_possible_values($cell, $sudoku)
	{
		$possible = [];
		for ($x = 1; $x <= 9; $x++) {
			if ($this->is_possible_number($cell, $x, $sudoku)) {
				array_unshift($possible, $x);
			}
		}

		return $possible;
	}

	/**
	 * calculate random possible value for cell?
	 *
	 * @param $possible
	 * @param $cell
	 *
	 * @return mixed
	 */
	private function determine_random_possible_value($possible, $cell)
	{
		return $possible[$cell][rand(0, count($possible[$cell]) - 1)];
	}

	/**
	 * Scan sudoku
	 *
	 * @param $sudoku
	 *
	 * @return bool
	 */
	private function scan_sudoku_for_unique($sudoku)
	{
		for ($x = 0; $x <= 80; $x++) {
			if ($sudoku[$x] == 0) {
				$possible[$x] = $this->determine_possible_values($x, $sudoku);
				if (count($possible[$x]) == 0) {
					return false;
					break;
				}
			}
		}

		return $possible;
	}

	/**
	 * Remove an attempt
	 *
	 * @param $attempt_array
	 * @param $number
	 *
	 * @return array
	 */
	private function removeAttempt($attempt_array, $number)
	{
		$new_array = [];
		for ($x = 0; $x < count($attempt_array); $x++) {
			if ($attempt_array[$x] != $number) {
				array_unshift($new_array, $attempt_array[$x]);
			}
		}

		return $new_array;
	}

	/**
	 * Output possible
	 *
	 * @param $possible
	 *
	 * @return string
	 */
	public function printPossible($possible)
	{
		$html = '<table bgcolor = "#ff0000" cellspacing = "1" cellpadding = "2">';
		for ($x = 0; $x <= 8; $x++) {
			$html .= '<tr bgcolor = "yellow" align = "center">';
			for ($y = 0; $y <= 8; $y++) {
				$values = '';
				for ($z = 0; $z <= count($possible[$x * 9 + $y]); $z++) {
					$values .= $possible[$x * 9 + $y][$z];
				}
				$html .= "<td width = \"20\" height = \"20\">$values</td>";
			}
			$html .= '</tr>';
		}
		$html .= '</table>';

		return $html;
	}

	/**
	 * Get next random
	 *
	 * @param $possible
	 *
	 * @return int
	 */
	public function getNextRandom($possible)
	{
		$max = 9;
		for ($x = 0; $x <= 80; $x++) {
			if (isset($possible[$x]) && count($possible[$x]) <= $max && count($possible[$x]) > 0) {
				$max = count($possible[$x]);
				$min_choices = $x;
			}
		}

		return $min_choices;
	}

	/**
	 * Solve sudoku
	 *
	 * @param $sudoku
	 */
	public function solve(&$sudoku)
	{
		$start = microtime();
		$saved = [];
		$saved_sud = [];
		$x = 0;

		while (!$this->is_solved_sudoku($sudoku)) {
			$x += 1;
			$next_move = $this->scan_sudoku_for_unique($sudoku);
			if ($next_move == false) {
				$next_move = array_pop($saved);
				$sudoku = array_pop($saved_sud);
			}

			$what_to_try = $this->getNextRandom($next_move);
			$attempt = $this->determine_random_possible_value($next_move, $what_to_try);
			if (count($next_move[$what_to_try]) > 1) {
				$next_move[$what_to_try] = $this->removeAttempt($next_move[$what_to_try], $attempt);
				array_push($saved, $next_move);
				array_push($saved_sud, $sudoku);
			}
			$sudoku[$what_to_try] = $attempt;
		}

		$end = microtime();
		$ms_start = explode(' ', $start);
		$ms_end = explode(' ', $end);
		$total_time = round(($ms_end[1] - $ms_start[1] + $ms_end[0] - $ms_start[0]), 2);
	}
}
