<!-- 
    PHP file
    Author: Salma Emjaheed
    Date: 12/14/2023
    Description:  
-->
<!DOCTYPE html>
<html>
<head>
	<title>Salma's Extremely Worthy Cause</title>
	<link rel="stylesheet" href="finalProject.css" />
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
	
	<script>
	$(document).ready(function () {
		dateTimeInterval();
		
		function dateTimeInterval() {
			displayDateTime();			
			interval = setInterval("displayDateTime()", 1000);
		}
		
		function displayDateTime() {
			// create a new date object with the current date and time
			let currentDate = new Date();
			
			// create a new date object with this date and time: February 14, 2024 at 7:00pm
			let eventDate = new Date(2024, 1, 14, 19, 0, 0); 
			
			// calculate the time difference between the event date and the current date for the countdown
			let timeDifference = eventDate - currentDate;

			// display the countdown if the even has not occured yet, otherwise display the message that the event occured 
			if (timeDifference > 0) {
				// calculate the number of days by dividing the total time difference by the number of milliseconds in a day
				let days = Math.floor(timeDifference / (1000 * 60 * 60 * 24));
				
				// calculate the number of hours remaining by taking the remainder of the total time difference when divided 
				// by the number of milliseconds in a day to divide the remainder by the number of milliseconds in an hour
				let hours = Math.floor((timeDifference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
				
				// calculate the number of minutes remaining by taking the remainder of the total time difference when divided
				// by the number of milliseconds in an hour to divides the remainder by the number of milliseconds in a minute
				let minutes = Math.floor((timeDifference % (1000 * 60 * 60)) / (1000 * 60));
				
				// calculate the number of seconds remaining by taking the remainder of the total time difference when divided
				// by the number of milliseconds in a minute to divides the remainder by the number of milliseconds in a second
				let seconds = Math.floor((timeDifference % (1000 * 60)) / 1000);

				// display the countdown
				let countdown = days + " Days " + hours + " Hours " + minutes + " Minutes " + seconds + " Seconds";
				$("#countDown").text(countdown);
			} else {
				// display message that event had passed
				$("#countDown").text("Woohoo! The big day has arrived!");
				// Remove the link and countdown message from the volunteer registration page
				$("#link, #countdownMessage").remove();
				// Stop the interval if the event has passed
				clearInterval(interval); 
			}
		}
		  });
	</script>
</head>
<body onload="dateTimeInterval()">
	<h1>Salma's Extremely Worthy Cause</h1>
	<p>
		Thank you for visiting our website to learn more about Salma's Extremely Worthy Cause. 
		This is where we would write something compelling to make you want to support our cause. 
		Take a minute to imagine something of great importance to you. Now assume that is the 
		foundation of Kristin's Extremely Worthy Cause!
	</p>
	
	<p>
		We will be holding our annual fundraiser for Salma's Extremely Worthy Cause on
	</p>
	
	<h2>
		<i>Feburary 14, 2024 at 7:00pm</i>
	</h2>
	
	<p> 
		We will need your help to make the event a success! 
		Please click on the link below to volunteer for Salma's Extremely Worthy Cause - you'll be glad you did!
	</p>
	
	<h3 id="countdownMessage">
		Countdown to the Big Event!
	</h3>
	<h3>
		<span id="countDown"></span>
	</h3>
	
	<a href="volunteerRegistration.php" id="link">Click here to register to volunteer!</a>
</body>
</html>