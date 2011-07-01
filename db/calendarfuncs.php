<?php
/*
* class to contain useful functions for generating dates within an academic year
* should be initialised with a list of start and end dates of terms, and it should calculate all other necessary dates
* eg weeks, months etc
*/
class calendarfuncs{

    protected $termdatelist;  //for each term, array( start, end )
    protected $timeformat;    //mysql or unix
    protected $firstdayofweek;//Sunday, Monday or another day
    public $readabledateformat = 'D Y-m-d H:i:s';
    //protected $weekdays = array( 'Sunday', ' Monday', ' Tuesday', ' Wednesday', ' Thursday', ' Friday', ' Saturday');

    public function __construct( $termdatelist=array() ){
        if( $termdatelist ){
            $this->termdatelist = $termdatelist;
        }
        else{
            $this->termdatelist = array(
                array( '2010-10-01', '2010-12-17' ),
                array( '2011-01-04', '2011-03-25' ),
                array( '2011-04-13', '2011-06-30' )
            );
        }
        $this->timeformat = 'mysql';
        $this->firstdayofweek = 1;    //1=Monday, 7=Sunday ... please do not use 0
    }
    
    public function display_calendar(){
        var_crap( $this->generate_dates() );
    }
    
    protected function generate_dates(){
        $dateinfo = array();
        $counter = 0;
        foreach( $this->termdatelist as $startend ){
            $counter++;
            $termstart = $startend[ 0 ];
            $termend = $startend[ 1 ];
            $termname = "TERM $counter";
            $dateinfo[] = $this->generate_sub_dates( $termstart, $termend, $termname );
        }
        return $dateinfo;
    }

    protected function generate_sub_dates( $start, $end, $name ){
        $startdayofweek = $this->calc_day_of_week( $start );
        $enddayofweek = $this->calc_day_of_week( $end );
        return array(
            'name' => $name,
            'start' => "$startdayofweek $start",
            'end' => "$enddayofweek $end",
            'months' => $this->calc_sub_month_limits( $start, $end ),
            'weeks' => $this->calc_sub_week_limits( $start, $end )
        );
    }

    protected function calc_sub_week_limits( $start, $end ){
        $dt_start = new DateTime( $start );
        $dt_end = new DateTime( $end );
        $weekslist = array();

        $tmp = clone( $dt_start );
	        $weekstart = clone( $tmp );
	        while( $tmp->format( 'N' ) != $this->firstdayofweek ){
	            $tmp = $tmp->modify( '+1 day' );
	        }
	        $next_weekstart = clone( $tmp );
	        $weekend = $tmp->modify( '-1 day' );
	        $weekslist[] = array( $weekstart->format( $this->readabledateformat ), $weekend->format( $this->readabledateformat ) );
            $tmp = clone( $next_weekstart );
        
            //now add complete weeks until we hit $end
        $counter = 0;
        while( $tmp->getTimestamp() < $dt_end->getTimestamp() ){
            $tmp = clone( $next_weekstart );
            $tmp->modify( '+ 6 day' );
            if( $tmp->getTimestamp() > $dt_end->getTimestamp() ){
                $next_weekend = clone( $dt_end );
            }
            else{
                $next_weekend = clone( $tmp );
            }
            $weekslist[] = array( $next_weekstart->format( $this->readabledateformat ) , $next_weekend->format( $this->readabledateformat ) );
            $next_weekstart = $tmp->modify( '+1 day' );
        }
        return $weekslist;
    }
    
    protected function calc_sub_month_limits( $start, $end ){
        $utime_start = $this->getutime( $start );
        $utime_end = $this->getutime( $end );

        if( $utime_end < $utime_start ){
            list( $utime_end, $utime_start ) = array( $utime_start, $utime_end );
        }

        $startmonth = date( 'n' , $utime_start );
        $endmonth = date( 'n' , $utime_end );
        $startyear = date( 'Y' , $utime_start );
        $endyear = date( 'Y' , $utime_end );

        $tmp = $utime_start;
        $tmpmonth = date( 'n', $tmp );
        $tmpyear = date( 'Y', $tmp );
            if( $tmpmonth == $endmonth && $tmpyear == $endyear ){
                $lastday = date( 'd', $utime_end );
            }
            else{
                $lastday = date( 't', $tmp );
            }
            $lastdateofmonth = mktime( 0, 0, 0, $tmpmonth, $lastday, $tmpyear );
        $monthdatelist = array( $this->getreadabletime( $tmp ), $this->getreadabletime( $lastdateofmonth ) );

        $tmpmonth = date( 'n', $tmp );
        $tmpyear = date( 'Y', $tmp );
        while( !( $tmpmonth == $endmonth && $tmpyear == $endyear ) ){
            $tmpmonth = date( 'n', $tmp );
            $tmpyear = date( 'Y', $tmp );
            //increment the month
            $newmonth = ( $tmpmonth + 1 );
            $newyear = $tmpyear;
            if( $newmonth > 12 ){
                $newmonth -= 12;
                $newyear++;
            }
            $tmp = mktime( 0, 0, 0, $newmonth, 1, $newyear );
            $tmpmonth = date( 'n', $tmp );
            $tmpyear = date( 'Y', $tmp );
            //test the condition again
            if( $tmpmonth == $endmonth && $tmpyear == $endyear ){
                $lastday = date( 'd', $utime_end );
            }
            else{
                $lastday = date( 't', $tmp );
            }
            $lastdateofmonth = mktime( 0, 0, 0, $tmpmonth, $lastday, $tmpyear );
            $monthdatelist[] = array( $this->getreadabletime( $tmp ), $this->getreadabletime( $lastdateofmonth ) );
        }
        //final month
            
        return $monthdatelist;
    }

    /*
    * take date in varying formats, and return consistent unix time
    * @param mixed $date
    * @return int
    */
    public function getutime( $date ){
        if( is_numeric( $date ) ){
            //assume unix time already
            $utime = $date;
        }
        else{
            //assume 'year-month-date'
            list( $year, $month, $date ) = $this->split_mysql_date( $date );
            $utime = mktime( 0, 0, 0, $month, $date, $year );
        }
        return $utime;
    }

    public function getreadabletime( $utime, $format='Y-m-d H:i:s' ){
        return date( $format, $utime );
    }

    protected function calc_day_of_week( $date ){
        $weekdaynum = date( 'N', $this->getutime( $date ) );
    }
    
    public function split_mysql_date( $date, $sep="-" ){
        return explode( $sep, $date );
    }
}
