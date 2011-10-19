<?php 
class ilp_percentage_bar {
	
	public	$failcolour;
	public	$passcolour;
	public	$neutralcolour;
	public	$passpercentage;
	public	$failpercentage;
	public 	$passfail;

    public  $total=100;    //can b overridden to modify display_bar
    //public  $actual=false;    
	
	function __construct($passfail=true,$failcolour=false,$passcolour=false,$neutralcolour=false)	{
		
		$this->failcolour		=	(!empty($failcolour)) ? $failcolour			:	get_config('block_ilp', 'failcolour');
		$this->passcolour		=	(!empty($passcolour)) ? $passcolour			:	get_config('block_ilp', 'passcolour');
		$this->neutralcolour	=	(!empty($neutralcolour)) ? $neutralcolour	:	get_config('block_ilp', 'midcolour');
		
		$passpercentage	=	get_config('block_ilp', 'passpercent');
		$failpercentage	=	get_config('block_ilp', 'failpercent');
		
		$this->passpercentage	=	(empty($passpercentage)) ? ILP_DEFAULT_PASS_PERCENTAGE : $passpercentage;
		$this->failpercentage	=	(empty($failpercentage)) ? ILP_DEFAULT_FAIL_PERCENTAGE : $failpercentage;
		
		$this->passfail			=	$passfail;
	}	
	
	function display_bar($percentage,$name='',$total=100,$size='medium')	{
		
		//are we using passfail colours to determine percentage bar colour?
        //no ...
/*
		if ($this->passfail) {
			if ($percentage	<= $this->passpercentage) $colour	=	 $this->failcolour;	
	    	       	
	    	if ($percentage	> $this->failpercentage && $percentage < $this->passpercentage) $colour	=	 $this->neutralcolour;	
	    	        	
	    	if ($percentage	>= $this->passpercentage) $colour	=	$this->passcolour;	
		} else {
			$colour	=	$this->passcolour;
		}	
*/
        //...we are using a single config variable
        $colour = get_config( 'block_ilp', 'progressbarcolour' );

        $actual = $percentage;
        if( 100 != $total ){
            $actual = round( $percentage * $total / 100 );
        }
        if( 100 < $percentage ){
            $percentage = 100;
        }
		$msg_numeric = "$actual/$total";
		return "<p><nobr><strong>{$name}</strong> <small>{$msg_numeric}</small></nobr><div class='percentagebar'  ><div style='width: {$percentage}%; height: 10px; background-color: {$colour}' ></div></div><br /></p>";		
	}
	
}

?>
