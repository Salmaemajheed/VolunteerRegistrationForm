<!-- 
    PHP file
    Author: Salma Emjaheed
    Date: 12/14/2023
    Description:  
-->
<?php
	session_start(); 
?>
<!DOCTYPE html>
<html>
<head>
	<title>Volunteer Registration</title>
	<link rel="stylesheet" href="finalProject.css" />
</head>
<body>
	<h1>Volunteer Registration</h1>
	<p>
		Thank you for your willingness to volunteer to help Salma's Extremely Worthy Cause. 
		Please fill out the form to sign up fo a shift. You may register for more than one slot. 
		Duplicte registrations (for the same slot) will not be processed.
	</p>
	<?php
		// establish a connection to MySQL database
		$hn = "localhost";
		$un = "mcuser";
		$pw = "Pa55word";
		$conn = new mysqli($hn, $un, $pw);
		if ($conn->connect_error) die("Fatal Error");	// if the there is an error during the connection to the database
		
		// create the form database if it does not already exist
		$query = "CREATE DATABASE IF NOT EXISTS form";
		$result = $conn->query($query);
		if (!$result) die("Fatal Error");
	
		// use form database
		$query = "USE form";
		$result = $conn->query($query);
		if (!$result) die("Fatal Error: " . mysqli_error($conn));
	
		// create volunteers table
		$query = "CREATE TABLE IF NOT EXISTS volunteers ( 
				volunteerId INT NOT NULL AUTO_INCREMENT,
				firstname VARCHAR(75),
				lastname VARCHAR(75),
				email VARCHAR(126),
				PRIMARY KEY (volunteerId))";
		$result = $conn->query($query);
		if (!$result) die("Fatal Error: " . mysqli_error($conn));
		
		// create shift table
		$query = "CREATE TABLE IF NOT EXISTS shift ( 
				shiftId INT AUTO_INCREMENT,
				startTime VARCHAR(126),
				endTime VARCHAR(126),
				PRIMARY KEY (shiftId))";
		$result = $conn->query($query);
		if (!$result) die("Fatal Error: " . mysqli_error($conn));
		
		// create volunteer_shift table
		$query = "CREATE TABLE IF NOT EXISTS volunteer_shift ( 
				shiftId INT AUTO_INCREMENT,
				volunteerId INT,
				FOREIGN KEY (volunteerId) REFERENCES volunteers (volunteerId),
				FOREIGN KEY (shiftId) REFERENCES shift (shiftId),
				PRIMARY KEY (shiftId, volunteerId))";
		$result = $conn->query($query);
		if (!$result) die("Fatal Error: " . mysqli_error($conn));
			
		$errFirstName = "";
		$errLastName = "";
		$first = "";
		$last = "";
		$email = "";
		$shift = "";
		$displayMessage = "";
		$remainingCount = "";
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$first = $_POST['fname'];
			$last = $_POST['lname'];
			$email = $_POST['email'];
			$shift = $_POST['shift'];
			$errorsFName = array();
			$errorsLName = array();
			$remainingCount = array();

			// validate first name
			if (!preg_match('/^[a-zA-Z ]+$/', $first)) {
				$errorsFName[] = "Please enter letters and spaces only.";
			}
			else if (!preg_match('/[A-Z]/', $first) && !preg_match('/[a-z]/', $first)) {
				$errorsFName[] = "First name must include at least one letter.";
			}
			
			// Display last name error message
			if (!empty($errorsFName)) {
				// Display error message
				foreach ($errorsFName as $error) {
					$errFirstName = $error;
				}
			}
				
			// validate last name
			if (!preg_match('/^[a-zA-Z ]+$/', $last)) {
				$errorsLName[] = "Please enter letters and spaces only.";
			}
			else if (!preg_match('/[A-Z]/', $last) && !preg_match('/[a-z]/', $last)) {
				$errorsLName[] = "Last name must include at least one letter.";
			}
			
			// Display last name error message
			if (!empty($errorsLName)) {
				// Display error message
				foreach ($errorsLName as $error) {
					$errLastName = $error;
				}
			}
			
			$delimiter = "-";
			function cutStringInHalf($shift, $delimiter)
			{
				// Explode the string into an array based on the delimiter
				$parts = explode($delimiter, $shift);

				// Assuming the array has at least two elements (start and end time)
				if (count($parts) >= 2) {
					return [
					'startTime' => trim($parts[0]),  // Trim to remove any leading/trailing whitespaces
					'endTime' => trim($parts[1]),
					];
				} else {
					// Handle the case where the string doesn't have enough parts
					return null;
				}
			}
			
			// Function to update remaining count
			function updateRemainingCount($conn, $shift) {
				// Get the count of registrations for the specific shift
				$time = cutStringInHalf($shift, $delimiter);
				$conn = new mysqli($hn, $un, $pw);
				$queryCount = "SELECT COUNT(*) AS count FROM shift WHERE startTime='{$time['startTime']}'";
				$resultCount = $conn->query($queryCount);
				if (!$resultCount) die("Fatal Error: " . mysqli_error($conn));

				if ($resultCount) {
					$row = $resultCount->fetch_assoc();
					$count = $row['count'];

					// Calculate the remaining count
					$remainingCount[$shift] = 5 - $count;
					
					// update the counts in the session
					 $_SESSION['remainingCount'][$shift] = $remainingCount[$shift];

					// Save the updated counts in the session
					return $remainingCount;
				}
			}
		
			// reflect the information in the database
			$shifts = ["6:00pm - 7:00pm", "7:00pm - 8:00pm", "8:00pm - 9:00pm", "9:00pm - 10:00pm", "10:00pm - 11:00pm"];
			foreach ($shifts as $shiftOption) {
				updateRemainingCount($conn, $shiftOption);
			}
			
			if (empty($errorsFName) && empty($errorsLName)) {
				
				$time = cutStringInHalf($shift, "-");
				// Check for duplicate registration
				$queryCheckDuplicate = "SELECT EXISTS (
					SELECT 1 FROM volunteer_shift
					JOIN shift ON volunteer_shift.shiftId = shift.shiftId
					JOIN volunteers ON volunteer_shift.volunteerId = volunteers.volunteerId
					WHERE volunteers.firstname = '$first' AND volunteers.lastname = '$last' AND volunteers.email = '$email'
					AND shift.startTime = '{$time['startTime']}' AND shift.endTime = '{$time['endTime']}'
					) AS isDuplicate";
				$resultCheckDuplicate = $conn->query($queryCheckDuplicate);
				if (!$resultCheckDuplicate) die("Fatal Error: " . mysqli_error($conn));

				$querySameVolunteer = "SELECT * FROM volunteers WHERE firstname='$first' AND lastname='$last' AND email = '$email'";
				$resultSameVolunteer = $conn->query($querySameVolunteer);
				
				$querySameShift = "SELECT * FROM shift WHERE startTime='{$time['startTime']}'";
				$resultSameShift = $conn->query($querySameVolunteer);
				
				if ($resultCheckDuplicate->num_rows == 0) {	// displayMessage is not displaying but everything else is working fine.
					$displayMessage = "<b>Duplicate registration not processed. Thanks, " . "$first!<b>";
					
				} else {
					// Your existing code for valid registration...
					if ($resultSameVolunteer->num_rows > 0) {
						$query = "SELECT volunteerID FROM volunteers WHERE firstname='$first' AND lastname='$last' AND email = '$email'";
						$result = $conn->query($query);
						if (!$result) die("Fatal Error: " . mysqli_error($conn));
						
						$volunteerID = $conn->insert_id;

						// the same volunteer will have to go for another shift, so always insert new shift
						$query = "INSERT INTO shift (startTime, endTime) VALUES ('{$time['startTime']}', '{$time['startTime']}')";
						$result = $conn->query($query);
						if (!$result) die("Fatal Error: " . mysqli_error($conn));
						
						$shiftId = $conn->insert_id;
						
						$query = "INSERT INTO volunteer_shift (volunteerID, shiftId) VALUES ($volunteerID, $shiftId)";
						$result = $conn->query($query);
						if (!$result) die("Fatal Error: " . mysqli_error($conn));
						

						$displayMessage = "<b>Thank you for being so generous with your time, " . "$first!<b>";
						
					} else {
						$query = "INSERT INTO volunteers (firstname, lastname, email) VALUES ('$first', '$last', '$email')";
						$result = $conn->query($query);
						if (!$result) die("Fatal Error");
						
						$volunteerID = $conn->insert_id;
						
						if ($resultSameShift->num_rows > 0) {
							$query = "SELECT shiftId FROM shift WHERE startTime='{$time['startTime']}'";
							$result = $conn->query($query);
							if (!$result) die("Fatal Error: " . mysqli_error($conn));
						} else {
							$query = "INSERT INTO shift (startTime, endTime) VALUES ('{$time['startTime']}', '{$time['startTime']}')";
							$result = $conn->query($query);
							if (!$result) die("Fatal Error: " . mysqli_error($conn));
						}
						$shiftId = $conn->insert_id;
						
						$query = "INSERT INTO volunteer_shift (volunteerID, shiftId) VALUES ($volunteerID, $shiftId)";
						$result = $conn->query($query);
						if (!$result) die("Fatal Error: " . mysqli_error($conn));
						
						$displayMessage = "<b>Thank you for registering to volunteer, " . "$first!<b>";
					}
				}
				
				// clear firs and last name, email, and shift if form is submitted and there are no errors
				$first = "";
				$last = "";
				$email = "";
				$shift = "";
			}
		}
	?>
	<form action="volunteerRegistration.php" method="post">
		<label for="fname" class="text">First Name</label>
		<input type="text" name="fname" value="<?php echo $first; ?>" required />
		<p class="errFirstName"><?php echo $errFirstName?></p>
		
		<label for="lname" class="text">Last Name</label>
		<input type="text" name="lname" value="<?php echo $last; ?>" required />
		<p class="errLastName"><?php echo $errLastName?></p>
		
		<label for="email" class="text">Email</label>
		<input type="email" name="email" value="<?php echo $email; ?>" required />
		
		<label for="shift">Volunteer Shift</label>
		<select id="shift" name="shift" size="5" required >
		<?php

		$shifts = ["6:00pm - 7:00pm", "7:00pm - 8:00pm", "8:00pm - 9:00pm", "9:00pm - 10:00pm", "10:00pm - 11:00pm"];
	
        foreach ($shifts as $shiftOption) {
			$remainingCount = updateRemainingCount($conn, $shiftOption);
            // Check if the remaining count for the shift is greater than 0
            if (isset($remainingCount[$shiftOption]) && $remainingCount[$shiftOption] > 0) {
                // Display the option with the updated string
                echo "<option value=\"$shiftOption\">$shiftOption ($remainingCount[$shiftOption] of 5 slots open)</option>";
            } else {
                // If count is 0, disable the option
                echo "<option value=\"$shiftOption\" disabled>$shiftOption: (0 of 5 slots open)</option>";
            }
        } 
		// close connection
		$conn->close();
		?>
		</select>
		<input type="submit" value="Register" />

	</form>
	<p id="displayMessage"><?php echo $displayMessage?></p>
</body>
</html>