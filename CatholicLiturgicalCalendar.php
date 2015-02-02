<?php 
/** 
	Catholic Liturgical Season Calculator
	by James Bergin (http://www.jamesbergin.com)
	
	This work is licensed under the Creative Commons Attribution-ShareAlike 3.0 Unported License. 
	To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/3.0/.
**/

// Global variables
$season = ''; // The liturgical season
$seasonTitle = ''; // The title of the season
$seasonalColour = ''; // The colour of the season

$isSeasonOverrideNoted = false;
$isCookieExpired = false;

/**
	Get Easter function
	
	For a given year, this function calculates the date of Easter in the Western calendar.
	
	The algorithm utilised below is known as the Meeus/Jones/Butcher algorithm and was originally sourced 
	from Marcos J. Montes over at http://www.smart.net/~mmontes/nature1876.html. 
	
	According to Marcos, "the actual origin of this algorithm appears to be by an anonymous correspondent 
	from New York to Nature in 1876."	
	
	More information is available at http://en.wikipedia.org/wiki/Computus#Algorithms
**/
function getEaster($year) {
  
	// Start point for calculations is 22nd of March
	$startEaster = mktime( 0,0,0, 3, 22, $year);
	
	// Algorithm for calculating Easter Sunday
	// Commonly used variables
	$yearM = ($year % 100);
	$year_D_100 = integerDivide($year, 100);
	$year_M_100_D_4 = integerDivide($yearM, 4);
	$year_D_100_D_4 = integerDivide($year_D_100, 4);
	$year_D_100_P_8_D_25 = integerDivide($year_D_100 + 8, 25);
	$yearSpecial1 = integerDivide((($year_D_100) - $year_D_100_P_8_D_25 + 1),3);
	
	$days = ((19 * ($year % 19) + $year_D_100 - $year_D_100_D_4 - $yearSpecial1 + 15) % 30) + ((32 + 2 * ($year_D_100 % 4) + 2 * $year_M_100_D_4 - ((19 * ($year % 19) + $year_D_100 - $year_D_100_D_4 - $yearSpecial1 + 15) % 30) - (($year % 100) % 4)) % 7) - 7 * integerDivide((($year % 19) + 11 * ((19 * ($year % 19) + $year_D_100 - $year_D_100_D_4 - $yearSpecial1 + 15) % 30) + 22 * ((32 + 2 * ($year_D_100 % 4) + 2 * $year_M_100_D_4 - ((19 * ($year % 19) + $year_D_100 - $year_D_100_D_4 - $yearSpecial1 + 15) % 30) - (($year % 100) % 4)) % 7)),451);
	
	$EasterSunday = strtotime("+".$days." days", $startEaster); 
	debug("Easter is " . $EasterSunday);
	return $EasterSunday;
}
/**
	Set Season function
	
	Uses the aforementioned code to provide the Catholic Liturgical season for a given date.
	It returns the major seasons (Lent, Advent, Easter, Christmas, Ordinary time) but also some for the 
	red vestments (i.e. Pentecost and Palm Sunday), and then the seasonTitles for some special days and
	feasts (e.g. Assumption)
**/
function setSeason($date, $shouldSetSeason = true) {
	global $seasonTitle;
	global $season;
	
	
	$strict = false; //allow for 'fun' colours, like green for St Patrick's day, and blue for the Assumption
	debug("Getting Season for the date provided: " . $date);
	$TheDate = $date;
	$year = date("Y", $date);
	debug("YEAR: " .$year);
	$shouldNoteColourOverride = false;
	
	// if $shouldSetSeason == false then we are manually overriding the colour, or we just want the name of the 
	// season to be returned, but the global variable to be left alone (useful if you are manually overriding the
	// colour).	
	if ($shouldSetSeason)
	{
		debug('Will set season, when we know what it is');
		$shouldNoteColourOverride = false;
	}
	else
	{
		debug('Will not set season; just getting title');
		$shouldNoteColourOverride = true;	
	}
	
	// Default to Ordinary time in case none of the other cases are valid
	if ($shouldSetSeason) $season = "Ordinary Time";
	$foundSeason = false;
	
	
	// Get main feasts for calculation
	
	//Easter
	$EasterSunday = getEaster($year);             
	debug("Using Easter, calculating the other days...");
	$PalmSunday = addDays($EasterSunday, -7);         
	$LaetareSunday = addDays($EasterSunday, -21);     
	$GoodFriday = addDays($EasterSunday, -2);
	$HolyThursday = addDays($EasterSunday, -3);
	$ShroveTuesday = addDays($EasterSunday, -47);      
	$AshWednesday = addDays($EasterSunday, -46);      
	$AscensionThursday = addDays($EasterSunday, 39);
	$Pentecost = addDays($EasterSunday, 49);
	$TrinitySunday = addDays($EasterSunday, 56);
	$CorpusChristi = addDays($EasterSunday, 60);
	
	// Advent
	// Christ the King is the Sunday on or after 20 November
	$ChristTheKing = strtotime( "20 November " . $year);
	//debug(date("Y-M-d", $ChristTheKing));
	if (date("D", $ChristTheKing) != "Sun") 
	{$ChristTheKing = addDays($ChristTheKing, (7 - (date("w", $ChristTheKing))));}
	
	// Once calculated, Christ the King can be used for the days of Advent
	$FirstSundayOfAdvent = addDays($ChristTheKing,7);
	$SecondSundayOfAdvent = addDays($ChristTheKing,14);
	$GaudeteSunday = addDays($ChristTheKing,21);
	$FourthSundayOfAdvent = addDays($ChristTheKing,28);
	$Christmas = strtotime("25 December " . date("y", $date));
	$LastChristmas = strtotime("25 December " . (date("y", $date) -1));
	
	// All Saints Day has white/gold vestments
	$AllSaintsDay = strtotime("1 November ".$year);
	
	// All Souls Day still has black vestments
	$AllSoulsDay = strtotime("2 November ".$year);
	
	// The date of the Epiphany is Jan 6th, and this is the end of the Octave of Christmas
	// We need to get this date from last Christmas for it to be valid for this year 
	// (i.e. no point getting the Epiphany in 2009 when the year is 2008!)
	
	$EpiphanyActual = addDays($LastChristmas,12);
	// But the observation is the first Sunday after that date
	if (date("D", $EpiphanyActual) != "Sun")
	{	$EpiphanyCelebration = addDays($EpiphanyActual, (7 - (date("w", $EpiphanyActual))));	}
	else
	{ $EpiphanyCelebration = $EpiphanyActual; }
	
	
	// The feast of the Baptism of Our Lord is celebrated on the Sunday after the observation of the Epiphany
	// In NZ, Ordinary time starts on the Baptism of Our Lord which is the Sunday after Epiphany (celebration)
	$BaptismOfOurLord = addDays($EpiphanyCelebration,7);
		
	// Our Lady of the Assumption is the patron of New Zealand.
	$FeastOfTheAssumption = strtotime("15 August ".$year);
	
	debug("If " . prettyDate($TheDate) . " >= " . prettyDate($BaptismOfOurLord) . " & " . prettyDate($TheDate) . " < " . prettyDate($AshWednesday) . " then ordinary.");
	if ( ( ($TheDate >= $BaptismOfOurLord) & ($TheDate < $AshWednesday)) || ( ($TheDate > $Pentecost) & ($TheDate < $FirstSundayOfAdvent)))
	{
		if ($shouldSetSeason) $season = "Ordinary Time";
		setSeasonTitle($shouldNoteColourOverride, false, "in Ordinary Time");		
		$foundSeason = true;
	}
		
	// Calculate the current season
	if (($TheDate >= $AshWednesday) & ($TheDate < $GoodFriday))
	{
		if ($shouldSetSeason) $season = "Lent";
		setSeasonTitle($shouldNoteColourOverride, false, "in the season of Lent");
		$foundSeason = true;
	}
	if (($TheDate >= $GoodFriday) & ($TheDate < $EasterSunday))
	{
		if ($shouldSetSeason) $season = "Triduum";
		setSeasonTitle($shouldNoteColourOverride, false, "in the Triduum");
		$foundSeason = true;
	}
	if (($TheDate >= $EasterSunday) & ($TheDate <= $Pentecost))
	{
		if ($shouldSetSeason) $season = "Easter";
		setSeasonTitle($shouldNoteColourOverride, false, "in the season of Easter");
		$foundSeason = true;
	}
	if (($TheDate >= $FirstSundayOfAdvent) & ($TheDate < $Christmas))
	{
		if ($shouldSetSeason) $season = "Advent";
		setSeasonTitle($shouldNoteColourOverride, false, "in the season of Advent");
		$foundSeason = true;
	}	
	
	// Deal with both last Christmas and this Christmas coming
	if (($TheDate >= $LastChristmas) & ($TheDate <= $BaptismOfOurLord))
	{
		if ($shouldSetSeason) $season = "Christmas";
		setSeasonTitle($shouldNoteColourOverride, false, "in the season of Christmas");
		$foundSeason = true;
	}
	
	if (($TheDate >= $Christmas))
	{
		if ($shouldSetSeason) $season = "Christmas";
		setSeasonTitle($shouldNoteColourOverride, false, "in the season of Christmas");
		$foundSeason = true;
	}
	
	// If we haven't found a season by now, it must be Ordinary Time
	if ($foundSeason == false)
	{
		if ($shouldSetSeason) $season = "Ordinary Time";
		setSeasonTitle($shouldNoteColourOverride, false, "in Ordinary Time");
		$foundSeason = true;
	}
	
	// Specific targets
	if (sameDayCheck($TheDate,  $LaetareSunday))
	{
		if ($shouldSetSeason) $season = "Lent (Laetare Sunday)";
		setSeasonTitle($shouldNoteColourOverride, true, "on Laetare Sunday");
		$foundSeason = true;
	}
	if (sameDayCheck($TheDate,  $GaudeteSunday))
	{
		if ($shouldSetSeason) $season = "Advent (Gaudete Sunday)";
		setSeasonTitle($shouldNoteColourOverride, true, "on Gaudete Sunday");
		$foundSeason = true;
	}
	if (sameDayCheck($TheDate,  $Pentecost))
	{
		if ($shouldSetSeason) $season = "Easter (Pentecost)";
		setSeasonTitle($shouldNoteColourOverride, true, "on Pentecost Sunday");
		$foundSeason = true;
	}
	if (sameDayCheck($TheDate,  $GoodFriday))
	{
		if ($shouldSetSeason) $season = "Triduum (Good Friday)";
		setSeasonTitle($shouldNoteColourOverride, true, "on Good Friday");
		$foundSeason = true;
	}
	if (sameDayCheck($TheDate,  $HolyThursday))
	{
		if ($shouldSetSeason) $season = "Triduum (Holy Thursday)";
		setSeasonTitle($shouldNoteColourOverride, true, "on Holy Thursday");
		$foundSeason = true;
	}
	
	if (sameDayCheck($TheDate,  $PalmSunday))
	{
		if ($shouldSetSeason) $season = "Easter (Palm Sunday)";
		setSeasonTitle($shouldNoteColourOverride, true, "on Palm Sunday");
		$foundSeason = true;
	}
	
	if (sameDayCheck($TheDate,  $AllSoulsDay))
	{
		if ($shouldSetSeason) $season = "All Souls Day";
		debug($seasonTitle);
		setSeasonTitle($shouldNoteColourOverride, true, "on All Souls Day");
		$foundSeason = true;
	}
	
	if (sameDayCheck($TheDate,  $AllSaintsDay))
	{
		if ($shouldSetSeason) $season = "All Saints Day";
		debug($seasonTitle);
		setSeasonTitle($shouldNoteColourOverride, true, "on All Saints Day");
		$foundSeason = true;
	}
	
	// Fixed Saint feast days
	$StPatrick = strtotime("17 March ".$year);
	$StPeterAndPaul = strtotime("29 June ".$year);
	$StJoseph = strtotime("19 March ".$year);
	$StFrancisDeSales = strtotime("24 January ".$year);
	
	// All Souls Day still has black vestments
	$AllSoulsDay = strtotime("2 November ".$year);
	
	//Not strictly true, but you gotta go green on the Feast of St Patrick!
	if ($strict == false)
	{
		if (sameDayCheck($TheDate,  $FeastOfTheAssumption))
		{
			if ($shouldSetSeason) $season = "Feast of the Assumption";
			debug($seasonTitle);		
			$foundSeason = true;
		}
		if (sameDayCheck($TheDate,  $StPatrick))
		{
			if ($shouldSetSeason) $season = "Feast of St Patrick";
			debug($seasonTitle);		
			$foundSeason = true;
		}
	}
	
	// Specific feast days and targets - these don't have specific colours, so we want
	// the season to stay the same, but to add these on to the titles.
	// For example, "Season of Lent, on the Feast of St Patrick"
	if (sameDayCheck($TheDate,  $ShroveTuesday))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on Shrove Tuesday");		
	}
	if (sameDayCheck($TheDate,  $EasterSunday))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on Easter Sunday!");		
	}
	if (sameDayCheck($TheDate,  $Christmas))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on Christmas Day!");		
	}	
	if (sameDayCheck($TheDate,  $AshWednesday))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on Ash Wednesday");		
	}	
	if (sameDayCheck($TheDate,  $FeastOfTheAssumption ))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on the Feast of the Assumption");
	}
	if (sameDayCheck($TheDate,  $StPatrick ))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on the Feast of St Patrick");
	}
	if (sameDayCheck($TheDate,  $StJoseph ))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on the Feast of St Joseph");
	}
	if (sameDayCheck($TheDate,  $StPeterAndPaul ))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on the Feast of St Peter and St Paul");
	}
	if (sameDayCheck($TheDate,  $AscensionThursday ))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on the Feast of the Ascension");
	}
	if (sameDayCheck($TheDate,  $BaptismOfOurLord ))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on the Feast of the Baptism of Our Lord");
	}	
	if (sameDayCheck($TheDate,  $StFrancisDeSales ))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on the Feast of St Francis de Sales");
	}
	if (sameDayCheck($TheDate,  $EpiphanyActual ))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on the Feast of the Epiphany");
	}
	if (sameDayCheck($TheDate,  $CorpusChristi ))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on the Feast of Corpus Christi");
	}
	if (sameDayCheck($TheDate,  $ChristTheKing ))
	{
		setSeasonTitle($shouldNoteColourOverride, true, "on the Feast of Christ the King");
	}
	
	if ($foundSeason)
	{
		debug('You are in the season of ' . getSeasonTitle());	
		return $season;
	}
	else
	{
		//should not get here
		debug("Don't know what season you are in for some reason?!  Defaulting to Ordinary Time");	
		return "Ordinary Time (default)";
	}
}
/**
	MAIN execution function that is called to set the colours for the season.
	It can be overridden two ways: the colour, and the date - both passed through the 
	query string in the browser.
**/
function setSeasonalColour() {
	global $seasonComboOption;
	global $isCookieExpired;
	/** Check for override cookie **/	
	if ( ($_COOKIE['BeingFrankSeasonColour'] != '') && ($isCookieExpired == false))
	{
		$cookieSeason = $_COOKIE['BeingFrankSeasonColour'];
		debug('COOKIE OVERRIDE:' . $cookieSeason);
		if ( isValidSeasonColour($cookieSeason,true) || ($cookieSeason == 'auto'))
		{
			$seasonComboOption = strtolower($cookieSeason);
			debug('seasonComboOption: ' . $seasonComboOption);
		}
		else
		{
			debug('Invalid cookie season');
		}
	}	
	/** If no cookie, check for query string **/	
	elseif ( ($_GET["dateOverride"] != "") && (strtotime($_GET["dateOverride"]) != false ))
	{
		debug('DATE OVERRIDE');
		setSeasonalColourForDate(strtotime($_GET["dateOverride"]));
		//print('DEBUG: dateOverride<br />');		
	}
	elseif ( ($_GET["colourOverride"] != "") && (isValidSeasonColour($_GET["colourOverride"],true)))
	{
		debug('COLOUR OVERRIDE');						
	}
    else
    {
		$time = current_time('timestamp', 0);
		setSeasonalColourForDate($time);
		
    }	    
}
/**
	Get Seasonal Colour - from date
	
	Pass in a date and get the corresponding liturgical colour
**/
function setSeasonalColourForDate($date) {
	/** checks to see if the global $seasonalColour variable is empty; if it is, then it populates**/
	global $seasonalColour;
	global $season; 
	
	if ($seasonalColour == '') 
	{
		setSeason($date, true);	
		$seasonalColour = getSeasonalColourFromSeason($season);	
	}		
}
/**
	Get Seasonal Colour - from season
	
	Pass in a liturgical season and get the corresponding colour
**/
function getSeasonalColourFromSeason($season) {
	if ( ($season == "Triduum (Good Friday)" ) || ($season == "All Souls Day"))
	{ return "black"; }
	else if ( ( $season == "Triduum (Holy Thursday)" ) || ( $season == "Triduum"))
	{ return "violet"; }
	else if ( ($season == "Feast of the Assumption") )
	{ return "blue"; }	
	else if ( ($season == "Easter (Pentecost)") || ($season == "Easter (Palm Sunday)"))
	{ return "red"; }
	else if ( ($season == "Advent (Gaudete Sunday)") || ($season == "Lent (Laetare Sunday)")) 
	{ return "pink"; }	
	else if ( ($season == "Lent") || ($season == "Advent") )
	{ return "violet"; }
	else if ( ($season == "Christmas") || ($season == "Easter") || ($season == "All Saints Day") )
	{ return "gold"; }	
	else
	{ return "green"; }
	
}
/** 
	Is Valid Season Colour function
	
	Check if a string (from user input) is a valid seasonal colour, and set the global variable if prompted.
	Useful for colour overrides.
**/
function isValidSeasonColour($colour,$setGlobal) {
	global $seasonalColour;
	$check = strtolower(htmlspecialchars($colour,ENT_QUOTES));
	if (($check == "blue") || ($check == "black") || ($check == "green") || ($check == "red") || ($check == "violet") || ($check == "gold") || ($check == "pink"))
	{
		if ($setGlobal) {$seasonalColour = $check;}
		return true;
	}
	else
	{
		debug('Invalid colour');
		return false;
	}
}
/** 
	Get Season Title function
	
	Gets the title of the season for using as an alt to images and the like
**/
function getSeasonTitle() {
	global $seasonTitle;
	echo $seasonTitle;
}
/** 
	Set Season Title function
	
	Set the global variable representing the title of the season for using as an alt to images 
**/
function setSeasonTitle($shouldNoteOverride, $append = false, $textToSetAsTitle) {
	global $seasonTitle;
	global $isSeasonOverrideNoted;
	
	if ($append) 
	{
		if ($shouldNoteOverride && !$isSeasonOverrideNoted)
		{ 
			$seasonTitle = $seasonTitle . $textToSetAsTitle . ' (but you have chosen to manually override the colour choice)';
			$isSeasonOverrideNoted = true;
			debug('Overridden season is ' . $seasonTitle);
		}
		else
		{	
			$seasonTitle = $seasonTitle . ' ' . $textToSetAsTitle;
			debug('Unconfirmed season is ' . $seasonTitle);
		}
		
	}
	else
	{
		if ($shouldNoteOverride && !$isSeasonOverrideNoted)
		{ 
			$seasonTitle = $textToSetAsTitle . ' (but you have chosen to manually override the colour choice)';
			$isSeasonOverrideNoted = true;
			debug('Overridden season is ' . $seasonTitle);
		}
		else
		{	
			$seasonTitle = $textToSetAsTitle;
			debug('Unconfirmed season is ' . $seasonTitle);
		}
		
	}
	
}

// Debug and utility functions
/** 
	Integer Divide function

	Performs integer division, which PHP can't support natively for some reason!
**/
function integerDivide($x, $y){
    //Returns the integer division of $x/$y.
    $t = 1;
    if($y == 0 || $x == 0)
        return 0;
    if($x < 0 XOR $y < 0) 
        $t = -1;
    $x = abs($x);
    $y = abs($y);
    $ret = 0;
    while(($ret+1)*$y <= $x)
        $ret++;
    return $t*$ret;
}
/** 
	Same Day Check function
	
	Checks a date against another one to see if they are the same
**/
function sameDayCheck( $date1, $date2){
	$comp1 = date("Y-M-d", $date1);
	$comp2 = date("Y-M-d", $date2);
	
	if ($comp1 == $comp2)
	{ return true; }
	else
	{ return false; }
}
/**
	Add Days function
	
	Adds a specified number of days to the date provided.
**/
function addDays($base, $amount){
	$timestring = "";
	if ($amount > 0)
	{
		$timestring = "+" . $amount . " days";
	}
	else
	{
		$timestring = $amount . " days";
	}
	$timestamp = strtotime($timestring, $base);	
	return $timestamp;	
}
/**
	Debug function
	
	Prints the provided text.
**/    
function debug($text){
	if (false)
	{ 	echo "<div class='debug' style='color:red; font-size: 11px; font-family: Verdana'>" . $text ."<br /></div>"; }
}
/** 
	Print Date function
	
	Prints the given date with the given title
**/
function printDate($title, $date){
	debug($title . " : " . date("D d M, Y", $date) . "<br />");
}
/** 
	Pretty Date function
	
	Formats the date in a more human-understandable format for debug 
**/
function prettyDate($date){
	return date("D d M, Y", $date);
}

?>