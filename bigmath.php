<?php 
class BigMath{
	public static function sum(BigNumber ...$numbers){
		$result = array();
		$place = 0;
		$highestPlace = 0;

		foreach($numbers as $number){
			$highestPlace = max(count($number->values), $highestPlace);
		}

		do{
			$total = 0;
			for($i = 0; $i < count($numbers); $i++){
				if(isset($numbers[$i]->values[$place])){
					$total += $numbers[$i]->values[$place];
				}
			}
			$result[] = $total;
			$place ++;
		}while($highestPlace > $place);
		return new BigNumber(array_reverse($result));
	}

	public static function sqrt(BigNumber $num, $stop){
		
		$result = new BigNumber(0);
		$left = new BigNumber(0);

		$pick = 0;
		$place = max(array_keys($num->values));
		// $resultPlace = ceil($place / 2) - 1;
		$diff = 0;

		if($place % 2 == 0){
			$pick = 1;
			$left->add($num->values[$place]);
		}else if($place % 2 == 1){
			$pick = 2;
			$left->add($num->value($place) * 10 + $num->value($place - 1));
		}

		$factor = 0;
		
		// while ($left >= pow($factor + 1, 2)) {
		while($left->operate(">=", pow($factor + 1, 2))){
			$factor ++;
		}

		//set record
		$result->add($factor);

		if($place < 0){
			$result->postivePlace ++;
		}

		//reset fop next calculating
		$place -= $pick;
		//since the second time, pick 2 numbers per loop
		$pick = 2;
		if(isset($num->values[$place - 1])){

		}
		$left->minus(pow($factor, 2));
		$left->multi(100)->add($num->value($place) * 10)->add($num->value($place - 1));

		//after second calculating
		$subtraction = new BigNumber(0);
		$formula = new BigNumber(0);
		$r = new BigNumber(0);

		while ($place >= -$stop * 2) {
			$factor = 0;
			$subtraction->toZero();
			$formula->toZero();
			$r->toZero();
			$r->add($result);
// var_dump($r->toString());
			//formula : (2 * (a * 10) + b) * b
			while ($left->operate(">=", $formula->add($r->multi(20))->add($factor + 1)->multi($factor + 1))) {
				$factor ++;
				$subtraction->toZero();
				$subtraction->add($formula);
				$formula->toZero();
				$r->toZero();
				$r->add($result);
				if($factor > 10){
					var_dump("formula error");
					break;
				}
			}

			$result->multi(10)->add($factor);
			if($place < 0){
				$result->postivePlace ++;
			}
			// reset for next loop
			$place -= $pick;

			$left->minus($subtraction);
			$left->multi(100)->add($num->value($place) * 10)->add($num->value($place - 1));
		}
		return $result;

	}
}

class BigNumber{
	public $values = array();
	public $postivePlace = 0;
	public $native = false;
	public function __construct($number){
		if(is_numeric($number)){
			// $this->set($number);
			if(preg_match("/\./", $number)){
				$posive = str_split(substr($number, 0, strpos($number, ".")));
				$afterpoint = str_split(substr($number, strpos($number, ".") + 1));
			}else{
				$posive = str_split($number);
				$afterpoint = array();
			}

			//set posive numer
			$this->values = array_reverse($posive);

			//set after point
			for($i = 0; $i < count($afterpoint); $i ++){
				$this->values[-1 - $i] = (int)$afterpoint[$i];
			}
			$this->carry();
		}else{
			throw new Exception("Wrong Parameter Type, I need string of numbers", 1);
		}

	}

	public function set($number, $place){
		if(is_numeric($number)){
			if(!isset($this->values[$place])){
				$this->values[$place] = 0;
			}
			$this->values[$place] = $number;
		}else{
			//ignore other data type
		}
	}

	public function value($place){
		if(!isset($this->values[$place])){
			$this->values[$place] = 0;
		}

		return $this->values[$place];
	}

	public function toString(){
		$values = $this->values;
		if($this->postivePlace != 0){
			array_splice($values, -$this->postivePlace, 0, array('.'));
		}
		$s = ($this->native ? "-" : "");
		$s .= implode('', $values);

		return $s;
	}

	public function carry(){
		$place = 0;

		while (isset($this->values[$place])) {
			$quotient = intval(floor($this->values[$place] / 10));
			$remainder = $this->values[$place] % 10;
			//keep the remainder, it is the result
			$this->values[$place] = $remainder;
			//next place does not exist, and need to carry
			if(!isset($this->values[$place + 1]) && $quotient != 0){
				$this->values[$place + 1] = $quotient;
			}else if(isset($this->values[$place + 1]) && $quotient != 0){
				$this->values[$place + 1] += $quotient;
			}
			$place ++;
		}

		//offset
		// if($this->postivePlace > 0){
		// 	$values = array();
		// 	foreach($this->values as $index => $value){
		// 		$values[$index - $this->postivePlace] = $value;
		// 	}

		// 	$this->values = $values;
		// }



		//keep the highest place is not 0
		krsort($this->values);
		reset($this->values);
		//check to left
		while (key($this->values) != 0 && pos($this->values) == 0) {
			//we dont use array_shift, it will reset index
			unset($this->values[key($this->values)]);
		}

		//check to right
		end($this->values);
		while (key($this->values) != 0 && pos($this->values) == 0) {
			unset($this->values[key($this->values)]);
			//after popping, the pointer will be reset to first
			end($this->values);
		}
		//reset pointer after cleaning up
		reset($this->values);
	}

	public function toZero(){
		$this->values = array(0);
	}

	private function getNumberArray($number){
		if(gettype($number) == "object"){
			$number_array = $number->values;
		}else{
			$number_array = array_reverse(str_split($number));
		}

		return $number_array;
	}

	//operations
	public function add($other){
		$number_array = $this->getNumberArray($other);
		for($p = 0; $p < count($number_array); $p ++){
			if(!isset($this->values[$p])){
				$this->values[$p] = 0;
			}
			$this->values[$p] += $number_array[$p];
		}

		$this->carry();
		return $this;
	}

	public function minus($other){
		$number_array = $this->getNumberArray($other);
		$subtracted = $this->values;
		$subtraction = $number_array;
		# compare values
		// compare place 
		if(count($this->values) < count($number_array)){
			$this->native = true;
		//the same place
		}else if(count($this->values) == count($number_array)){
			$p = max(array_keys($this->values));
			$min = min(array_keys($this->values));
			//search diff number follow by place

			while(isset($this->values[$p]) && isset($number_array[$p]) && $min < $p && $this->values[$p] == $number_array[$p]){
				$p --;
			}
			//compare which greater is
			if($this->values[$p] < $number_array[$p]){
				$this->native = true;
			}
		}

		//keep subtracted number bigger than subtraction
		if($this->native){
			$subtracted = $number_array;
			$subtraction = $this->values;
		}

		// start calculating
		for($p = 0; $p < count($subtracted); $p ++){
			if(!isset($subtraction[$p])){
				$this->values[$p] = 0;
			}

			//borrowing
			if(!isset($subtraction[$p])){
				$subtraction[$p] = 0;
			}
			if($subtracted[$p] < $subtraction[$p]){
				$subtracted[$p] += 10;
				$subtracted[$p + 1] -= 1;
			}
			$subtracted[$p] -= $subtraction[$p];
		}

		//set result
		$this->values = $subtracted;

		return $this;
	}

	public function multi($other){
		$number_array = $this->getNumberArray($other);
		$result = array();

		for($p = 0; $p < count($this->values); $p++){
			for($po = 0; $po < count($number_array); $po++){
				if(!isset($result[$p + $po])){
					$result[$p + $po] = 0;
				}
				$result[$p + $po] += ($this->values[$p] * $number_array[$po]);
			}
		}
		$this->values = $result;
		$this->carry();

		return $this;
	}

	public function divide($other, $stop = 0){
		$number_array = $this->getNumberArray($other);
		$result = array();

		$pick = count($number_array);
		$place = $this->place() - 1 - ($pick - 1);

		$divisor = (int)implode("", array_reverse($number_array));
		$dividend = (int)implode("", array_reverse(array_splice($this->values, -$pick, $pick)));
		$diff = 0;
		while($place >= -$stop){
			$factor = 0;
			//try the closest values
			while ($dividend >= $divisor * ($factor + 1)){
				$factor ++;
			}
			//set record
			array_unshift($result, $factor);
			if($place < 0){
				$this->postivePlace ++;
			}
			//reset for next loop
			$diff = $dividend - ($divisor * $factor);
			$place --;

			// patch 0
			if(!isset($this->values[$place])){
				$this->values[$place] = 0;
			}
			$dividend = $diff * 10 + ($this->values[$place]);
		}

		$this->values = $result;
		$this->carry();
		return $this;
	}

	public function operate($operator, $number){
		$number_array = $this->getNumberArray($number);

		$result = false;
		switch ($operator) {
			case '<':
	
				break;
			case '<=':
				# code...
				break;
			case '>':
				# code...
				break;
			case '>=':
				if(count($this->values) > count($number_array)){
					$result = true;
				//the same place
				}else if(count($this->values) == count($number_array)){
					$p = max(array_keys($this->values));
					$min = min(array_keys($this->values));
					
					//search diff number follow by place
					while(isset($this->values[$p]) && isset($number_array[$p]) && $this->values[$p] == $number_array[$p] && $min < $p){
						$p --;
					}
					//compare which greater is
					if($this->values[$p] >= $number_array[$p]){
						$result = true;
					}
				}
				# code...
				break;
			case '==':
				# code...
				break;
		}

		return $result;
	}
}

