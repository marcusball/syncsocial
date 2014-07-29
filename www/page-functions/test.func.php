<?php
class Page extends PageObject{
	public function printTitle(){
		echo "An awesome title";
	}
	
	public function someOtherPrint(){
		echo "Some some awesome text";
		
		/*
		 * This example uses the arg values resulting from the $REWRITE_RULES option in config.php.
		 * the Args values contains the value captured by named capture groups in the $REWRITE_RULES regular expressions.
		 * Use issetArg to test if a named group was captured, and then use arg() to access the value captured.
		 */
		if($this->issetArg('num')){
			$inputNum = $this->arg('num');
			echo "<div>Wow, {$inputNum} is a really cool number</div>"; 
		}
	}
}
?>