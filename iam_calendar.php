<?php
    /**
    * This is the IAM_Calendar class. It provides methods for generating HTML code for a 1-month calendar, with various content and styling options
    * @package IAM_Classes      
    * @author Iván Melgrati <imelgrat@gmail.com>
    * @version 1.0
    */
    
    
    /**
    * This class draws a 1-month calendar, with styling options.
    * 
    * The class draws a calendar, with optional checkboxes in each day (to be used in a form, for instance).
    * Each part of the calendar uses a configurable CSS style class, allowing the user to style it according to his/her needs.
    * @package IAM_Classes
    * @subpackage IAM_Calendar      
    * @author Iván Melgrati <imelgrat@gmail.com>
    * @version 1.0
    */
    class IAM_Calendar
    {
	 	/**
        * @access private
        * @var int Keeps the calendar year
        **/
        private $year;
        
        /**
        * @access private
        * @var int Keeps the calendar month
        **/
        private $month;
        
        /**
        * @access private
        * @var int Keeps the number of days in current month
        **/
        private $monthDays;        

        /**
        * @access private
        * @var Array Keeps holidays. If a day is holiday, it will change the CSS class to distinguish it.
        **/
        private $holidays; 
		
		private $selectedDay; 
                
        /**
        * @access private
        * @var Array Keeps the names of days of the week.
        **/
        private $dayNames = array(0=>'Sun', 1=>'Mon', 2=>'Tue', 3=>'Wed', 
                                    4=>'Thu' , 5=>'Fri' , 6=>'Sat');
                                    
        /**
        * @access private
        * @var Array keeps the names of months of the year
        **/                                    
        private $monthNames = array(1=>'January', 2=>'February', 3=>'March', 4=>'April' , 5=>'May' , 6=>'June' ,
                                    7=>'July', 8=>'August', 9=>'September', 10=>'October', 11=>'November' , 12=>'December');
        
        /**
        * @access private
        * @var Array keeps the style classes for each part of the calendar table. The default values are the names used to identify each part
        **/                                        
        private $styleNames = array ('tableStyle'=>'tableStyle', 'monthHeaderStyle'=>'monthHeaderStyle', 'dayHeaderStyle'=>'dayHeaderStyle', 
                                     'nonMonthStyle'=>'nonMonthStyle', 'weekdayStyle'=>'weekdayStyle', 'weekendStyle'=>'weekendStyle',
                                     'holidayStyle'=>'holidayStyle', 'inputStyle'=>'inputStyle');
        
        /**
        * Determine whether the year is a leap year.
        *
        * @param   int $year        The year as a four digit integer
        * @return  boolean          True if the year is a leap year, false otherwise
        */
        private function isLeapYear($year)
        {
            return $year % 4 == 0 && ($year % 400 == 0 || $year % 100 != 0);
        }

        /**
        * Class constructor
        * 
        * Takes year and month as optional parameters.
        * 
        * @param int $year Must be an integer greater than 1900. If not given or in case of error, the current year will be used.
        * @param int $month  Must be an integer between 1 and 12. If not given or in case of error, the current month will be used.    
        */
        public function IAM_Calendar($year=0, $month=0)
        {
			$this->selectedDay = 0;
	        $this->setYear($year); 
            $this->setMonth($month);
            $this->holidays=Array();
        }
        
        /**
         * Sets the calendar's current year.
         * 
         * @param int $year Must be an integer greater than 1900. If not given or in case of error, the current year will be used.
         */
        public function setYear($year)
        {
            $this->year = ($year and is_numeric($year) and $year > 1900) ? $year : date('Y');       
        }
        
        /**
         * Sets the calendar's current month. 
         * 
         * @param int $month Must be an integer between 1 and 12. If not given or in case of error, the current month will be used.    
         */
        public function setMonth($month=0)
        {
            $this->month = ($month and is_numeric($month) and $month >=1 and $month <=12)? $month :  date("n");
            $this->monthDays = $this->numberOfDays($this->year, $month);
        }
        
        /**
        * Set the CSS class for a calendar element. If the element doesn't exist, it does nothing
        * @param string $element Name of the element to apply the CSS class to
        * @param string $cssClass Name of the CSS class to be applied 
        */ 
        public function setStyle($element, $cssClass)
        {
            if(array_key_exists($element, $this->styleNames))
            {
                $this->styleNames[$element] = $cssClass; 
            }    
        }
        
        /**
        * Set the names of the days of the week
        * @param  $days Array Array of 7 strings containing the days of the week (starting from Sunday)
        */
        public function setDayNames($days)
        {
            if(sizeof($days)==7)
            {
                $counter = 0;
                foreach($days as $day)
                {
                    $this->dayNames[$counter++]=$day;
                }
            }
        }
        
        /**
        * Set the names of the months of the year
        * @param  Array Array of 7 strings containing the months of the year (starting from January)
        */
        public function setMonthNames($months)
        {
            if(sizeof($months)==12)
            {
                $counter = 0;
                foreach($months as $month)
                {
                    $this->monthNames[$counter++]=$month;
                }
            }
        }
        
        /**
        * Returns the number of days for the given year and month. It accounts for leap years.
        * @param int $year
        * @param int $month 
        */
        public function numberOfDays($year, $month)
        {
            if (in_array($month, array(1, 3, 5, 7, 8, 10, 12)))
                return 31;
            else if (in_array($month, array(4, 6, 9, 11)))
                return 30;     
            else
            {
                return ($this->isLeapYear($year) ? 29 : 28);           
            }
        }
		
		private $pageUrl;
		public function SetUrl( $url )
		{
			$this->pageUrl = $url;
		}
        
        /**
        * Add a holiday to the list. This will change the CSS styling and pop the description when placing the mouse over. Only one holiday is allowed per day.
        * @param int $year Year of the event
        * @param int $month Month of the event
        * @param int $day Day of the event
        * @param string $desc Event description
        */
        public function addHoliday($year, $month, $day, $desc='')
        {
            $this->holidays[$year][$month][$day]=$desc;
			$this->selectedDay = $day;
        }
        
        /**
        * Generates HTML code for current year and month
        * 
        * @param boolean $drawDayHeaders       When set, it generates a header row containing the name of each day (Mon, Tue....)
        * @param boolean $drawMonthHeader      When set, it generates a header row containing the name of the current month
        * @param boolean $drawOtherDays        When set, it shows the day numbers from previous and next months.
        * @param boolean $drawInputControl     When set, it draws a chechbox next to each day. The checkbox's name is of the form yyyy-mm-dd
        * @param boolean $disableMultiple      When set (and if $drawInputControl has been set), it will draw a radiobutton instead of a checkbox
        * @param string  $radioName            Day of the radio group (used when @link $disableMultiple is set). It defaults to 'cal'. Each button's value is of the form yyyy-mm-dd 
        */
        public function drawMonth( $daysLink=true,
								$drawDayHeaders=true, $drawMonthHeader=true, $drawOtherDays=true, 
                                  $drawInputControl=false, $disableMultiple=false, $radioName='calendar') 
        {
            // Get index of first and last day of the month
	        $startingDay = date("w", mktime(0, 0, 0, $this->month, 1, $this->year));
            $endingDay = date("w", mktime(0, 0, 0, $this->month, $this->monthDays, $this->year));

			$prevMonth = $this->month == 1 ? 12 : $this->month - 1;
			$prevYear =  $this->month == 1 ? ($this->year - 1) : ($this->year);
			$nextMonth = $this->month == 12 ? 1 : $this->month + 1;
			$nextYear =  $this->month == 12 ? ($this->year + 1) : ($this->year);
			
	        
            //Begin drawing calendar
            $returnCode = '<table class="'.$this->styleNames['tableStyle'].'">';    
            
            // Draw Table headers containing dayNames (if specified in parameter list)     
            if ($drawMonthHeader)
            {
                $returnCode .= '<tr><td colspan="7" class="'.$this->styleNames['monthHeaderStyle'].'">';
				$returnCode .= "\n".'<span class="left">';
				$returnCode .= '<a href="'.$this->pageUrl."Year=$prevYear&amp;Month=$prevMonth&amp;Day=".$this->selectedDay."\"> &lt;&lt; </a> ";
			    $returnCode .= '</span>';
				$returnCode .= $this->monthNames[$this->month].' '.$this->year; 
				$returnCode .= '<span class="right">';
				$returnCode .= ' <a href="'.$this->pageUrl."Year=$nextYear&amp;Month=$nextMonth&amp;Day=".$this->selectedDay."\"> >> </a>";
			    $returnCode .= "</span>\n";
		        $returnCode .= '</td>'."</tr>";
            } 
                        
            // Draw Table headers containing dayNames (if specified in parameter list)
            if ($drawDayHeaders)
            {
                $returnCode .= "\n<tr>";
		        for ($count = 0; $count < 7; $count++) 
                {
			        $returnCode .= '<td class="'.$this->styleNames['dayHeaderStyle'].'">'.$this->dayNames[$count].'</td>';
		        }
		        $returnCode .= "</tr>\n";
            } 
            
            $count = 0;  
            $returnCode .= "<tr>";   
            
            // Draw days from previous month   
            if($startingDay  > 0)
            {
                $prevDays= $this->numberOfDays($prevYear, $prevMonth)-$startingDay+1;
                
                for ($count = 0; $count < $startingDay; $count++) 
                {
                    $returnCode .=  '<td class="'.$this->styleNames['nonMonthStyle'].'">';
                    
                    if($drawOtherDays)
                    {
                        if($drawInputControl)
                        {
                            if($disableMultiple)
                            $returnCode .= '<input type="radio" class="'.$this->styleNames['inputStyle'].'" name="'.$radioName.'" value="'.
                                            $this->year.'-'.sprintf("%02s", $this->month).'-'.sprintf("%02s", $prevDays).'">';                            
                            else
                            $returnCode .= '<input type="checkbox" class="'.$this->styleNames['inputStyle'].'" name="'.
                                            $this->year.'-'.sprintf("%02s", $this->month).'-'.sprintf("%02s", $prevDays).'">';        
                        } 
                        
                        $returnCode .=  sprintf("%02s", $prevDays++)."</td>\n";                    
                    }
                    else
                        $returnCode .= "</td>\n";

		        }            
            }
		    
            // Print day numbers
		    for ($counter = 1; $counter <= $this->monthDays; $counter++, $count++) 
            {
			    if (($count % 7) == 0 and $count != 0) 
                {
				    $returnCode .= "</tr>\n<tr>";
			    }
                
				if ($this->holidays[$this->year][$this->month][$counter] !="")
					$returnCode .= "<td class=".$this->styleNames['holidayStyle']." title=\"".$this->holidays[$this->year][$this->month][$counter]."\">";   
				else	
					if( (($count % 7) == 0) or (($count % 7) == 6) )
						$returnCode .= "<td class=".$this->styleNames['weekendStyle'].">";
                    else
                        $returnCode .= "<td class=".$this->styleNames['weekdayStyle'].">"; 
                
                if($drawInputControl)
                {
                    if($disableMultiple) 
                        $returnCode .= '<input type="radio" class="'.$this->styleNames['inputStyle'].'" name="'.$radioName.'" value="'.
                                    $this->year.'-'.sprintf("%02s",$this->month).'-'.sprintf("%02s",$counter).'">';                        
                    else
                        $returnCode .= '<input type="checkbox" class="'.$this->styleNames['inputStyle'].'" name="'.
                                    $this->year.'-'.sprintf("%02s",$this->month).'-'.sprintf("%02s",$counter).'">';        
                }    
                
				if( $daysLink )
				{
					$returnCode .= "<a href=\"".$this->pageUrl."Year=".$this->year."&amp;Month=".$this->month."&amp;Day=$counter\">";
				}
                $returnCode .= sprintf("%02s",$counter);
								
				if( $daysLink )
				{
					$returnCode .= '</a>';
				}
                $returnCode .= "</td>\n";
				
				
		    }

            // Draw days from next month   
            if($endingDay < 6)
            {
                $dayCounter=1;
                for ($count = $endingDay; $count < 6; $count++) 
                {
                    $returnCode .=  '<td class="'.$this->styleNames['nonMonthStyle'].'">';
                    
                    if($drawOtherDays)
                    {
                        if($drawInputControl)
                        {
                            if($disableMultiple)
                                $returnCode .= '<input type="radio" class="'.$this->styleNames['inputStyle'].'" name="'.$radioName.'" value="'.
                                            $this->year.'-'.sprintf("%02s", $this->month).'-'.sprintf("%02s", $dayCounter).'">';                            
                            else
                                $returnCode .= '<input type="checkbox" class="'.$this->styleNames['inputStyle'].'" name="'.
                                            $this->year.'-'.sprintf("%02s", $this->month).'-'.sprintf("%02s", $dayCounter).'">';        
                        } 
                        $returnCode .=  sprintf("%02s", $dayCounter++).'</td>';                    
                    }
                    else $returnCode .= '</td>';
		        }            
            }
            
		    $returnCode .= '
			</tr></table>';
		        
	        return $returnCode;
        }
	}

?>