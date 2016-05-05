<?php

class Sudoku extends Service
{
	/**
	 * Function executed when the service is called
	 *
	 * @param Request
	 * @return Response
	 * */
	public function _main(Request $request)
	{
		$sudoku = array();
		for($i = 0; $i < 81; $i ++) $sudoku[] = 0;

		$this->solve($sudoku);

		$solution = $sudoku;
		
		$hide = mt_rand(40, 60);
		
		for($i = 0; $i < $hide; $i ++)
		{
			$p = mt_rand(0, 80);
			$sudoku[$p] = 0;
			$solution[$p] = $solution[$p] * - 1;
		}

		$htmlproblem = $this->print_sudoku($sudoku);
		$htmlsolution = $this->print_sudoku($solution);
		$forprint = $this->print_sudoku($sudoku, true);

		// create response
		$responseContent = array(
			"solution" => $htmlsolution,
			"problem" => $htmlproblem,
			"problem_print" => $forprint
		);

		$response = new Response();
		$response->setResponseSubject("El Sudoku que usted pidio");
		$response->createFromTemplate("basic.tpl", $responseContent);
		return $response;
	}
	
	
	private function return_row($cell)
	{
		return floor($cell / 9);
	}
	
	
	private function return_col($cell)
	{
		return $cell % 9;
	}
	
	
	private function return_block($cell)
	{
		return floor($this->return_row($cell) / 3) * 3 + floor($this->return_col($cell) / 3);
	}


	private function is_possible_row($number, $row, $sudoku)
	{
		$possible = true;
		for($x = 0; $x <= 8; $x ++)
		{
			if ($sudoku[$row * 9 + $x] == $number)
			{
				$possible = false;
			}
		}
		return $possible;
	}


	private function is_possible_col($number, $col, $sudoku)
	{
		$possible = true;
		for($x = 0; $x <= 8; $x ++)
		{
			if ($sudoku[$col + 9 * $x] == $number)
			{
				$possible = false;
			}
		}
		return $possible;
	}


	function is_possible_block($number, $block, $sudoku)
	{
		$possible = true;
		for($x = 0; $x <= 8; $x ++)
		{
			if ($sudoku[floor($block / 3) * 27 + $x % 3 + 9 * floor($x / 3) + 3 * ($block % 3)] == $number)
			{
				$possible = false;
			}
		}
		return $possible;
	}


	private function is_possible_number($cell, $number, $sudoku)
	{
		$row = $this->return_row($cell);
		$col = $this->return_col($cell);
		$block = $this->return_block($cell);
		return $this->is_possible_row($number, $row, $sudoku) and $this->is_possible_col($number, $col, $sudoku) and $this->is_possible_block($number, $block, $sudoku);
	}


	private function print_sudoku($sudoku, $for_print = false)
	{
		$html = "<table align = \"center\" cellspacing = \"1\" cellpadding = \"2\">\n";

		for($x = 0; $x <= 8; $x ++)
		{
			$html .= "<tr align = \"center\">\n";
			for($y = 0; $y <= 8; $y ++)
			{
				$style = 'border-right: 1px solid gray;';
				
				if (($x + 1) % 3 == 0) $style .= 'border-bottom: 3px solid black;';
				else $style .= 'border-bottom: 1px solid gray;';

				if (($y + 1) % 3 == 0) $style .= 'border-right: 3px solid black;';
				if ($x == 0) $style .= 'border-top: 3px solid black;';
				if ($y == 0) $style .= 'border-left: 3px solid black;';

				$v = $sudoku[$x * 9 + $y];
				
				if ($v < 0) $v = 0 - $v;
				elseif ($v == 0) $v = '&nbsp;';

				if ($v <= 0) $style .= "background: white;";
				else $style .= "background: #dddddd;";

				$html .= "<td width = \"40\" height = \"40\" style=\"$style;font-size:25px;font-family:verdana;\">";

				if ($v == '&nbsp;')
				{
					if ($for_print) $html .= '&nbsp;';
					else
					{
						$html .= '<select style="padding: 3px; border: none; background:white;"><option value="-">&nbsp;</option>';
						for($i = 1; $i <= 9; $i ++) $html .= '<option value="' . $i . '">' . $i . '</option>';
						$html .= '</select>';
					}
				}
				else $html .= $v;

				$html .= "</td>\n";
			}
			$html .= "</tr>\n";
		}
		$html .= "</table>\n";

		return $html;
	}
	
	
	private function is_correct_row($row, $sudoku)
	{
		for($x = 0; $x <= 8; $x ++)
		{
			$row_temp[$x] = $sudoku[$row * 9 + $x];
		}
		return count(array_diff(array(1,2,3,4,5,6,7,8,9), $row_temp)) == 0;
	}


	private function is_correct_col($col, $sudoku)
	{
		for($x = 0; $x <= 8; $x++)
		{
			$col_temp[$x] = $sudoku[$col + $x * 9];
		}
		return count(array_diff(array(1,2,3,4,5,6,7,8,9), $col_temp)) == 0;
	}


	private function is_correct_block($block, $sudoku)
	{
		for($x = 0; $x <= 8; $x++)
		{
			$block_temp[$x] = $sudoku[floor($block / 3) * 27 + $x % 3 + 9 * floor($x / 3) + 3 * ($block % 3)];
		}
		return count(array_diff(array(1,2,3,4,5,6,7,8,9), $block_temp)) == 0;
	}


	private function is_solved_sudoku($sudoku){
		for($x = 0; $x <= 8; $x ++) {
			if (!$this->is_correct_block($x, $sudoku) or ! $this->is_correct_row($x, $sudoku) or !$this->is_correct_col($x, $sudoku)) {
				return false;
				break;
			}
		}
		return true;
	}
	
	private function determine_possible_values($cell, $sudoku)
	{
		$possible = array();
		for($x = 1; $x <= 9; $x ++)
		{
			if ($this->is_possible_number($cell, $x, $sudoku))
			{
				array_unshift($possible, $x);
			}
		}
		return $possible;
	}

	
	private function determine_random_possible_value($possible, $cell)
	{
		return $possible[$cell][rand(0, count($possible[$cell]) - 1)];
	}

	
	private function scan_sudoku_for_unique($sudoku)
	{
		for($x = 0; $x <= 80; $x ++)
		{
			if ($sudoku[$x] == 0)
			{
				$possible[$x] = $this->determine_possible_values($x, $sudoku);
				if (count($possible[$x]) == 0)
				{
					return false;
					break;
				}
			}
		}
		return $possible;
	}

	
	private function remove_attempt($attempt_array, $number)
	{
		$new_array = array();
		for($x = 0; $x < count($attempt_array); $x ++)
		{
			if ($attempt_array[$x] != $number)
			{
				array_unshift($new_array, $attempt_array[$x]);
			}
		}
		return $new_array;
	}

	
	function print_possible($possible)
	{
		$html = "<table bgcolor = \"#ff0000\" cellspacing = \"1\" cellpadding = \"2\">";
		for($x = 0; $x <= 8; $x ++)
		{
			$html .= "<tr bgcolor = \"yellow\" align = \"center\">";
			for($y = 0; $y <= 8; $y ++)
			{
				$values = "";
				for($z = 0; $z <= count($possible[$x * 9 + $y]); $z ++)
				{
					$values .= $possible[$x * 9 + $y][$z];
				}
				$html .= "<td width = \"20\" height = \"20\">$values</td>";
			}
			$html .= "</tr>";
		}
		$html .= "</table>";
		return $html;
	}


	function next_random($possible)
	{
		$max = 9;
		for($x = 0; $x <= 80; $x ++)
		{
			if (isset($possible[$x]) && count($possible[$x]) <= $max && count($possible[$x]) > 0)
			{
				$max = count($possible[$x]);
				$min_choices = $x;
			}
		}
		return $min_choices;
	}


	function solve(&$sudoku)
	{
		$start = microtime();
		$saved = array();
		$saved_sud = array();
		$x = 0;

		while ( ! $this->is_solved_sudoku($sudoku))
		{
			$x += 1;
			$next_move = $this->scan_sudoku_for_unique($sudoku);
			if ($next_move == false)
			{
				$next_move = array_pop($saved);
				$sudoku = array_pop($saved_sud);
			}

			$what_to_try = $this->next_random($next_move);
			$attempt = $this->determine_random_possible_value($next_move, $what_to_try);
			if (count($next_move[$what_to_try]) > 1)
			{
				$next_move[$what_to_try] = $this->remove_attempt($next_move[$what_to_try], $attempt);
				array_push($saved, $next_move);
				array_push($saved_sud, $sudoku);
			}
			$sudoku[$what_to_try] = $attempt;
		}

		$end = microtime();
		$ms_start = explode(" ", $start);
		$ms_end = explode(" ", $end);
		$total_time = round(($ms_end[1] - $ms_start[1] + $ms_end[0] - $ms_start[0]), 2);
	}	
}
