<!-- 
    PHP file
    Author: Salma Emjaheed
    Date: 12/14/2023
    Description:  This is a PHP file for Salma's Worthy Cause. 
				It includes the the form for registration and 
				the php code for retrieving and inputing information
-->
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
			
	$delimiter = "-";
	function cutStringInHalf($shift, $delimiter) {
		// Explode the string into an array based on the delimiter
		$parts = explode($delimiter, $shift);

		// Assuming the array has at least two elements (start and end time)
		if (count($parts) >= 2) {
			return [
			'startTime' => trim($parts[0]),  // Trim to remove any leading/trailing whitespaces
			'endTime' => trim($parts[1]),
			];
		} 
	}
		
	function updateRemainingCount($conn, $shift) {
		// Get the count of registrations for the specific shift
		$delimiter = "-";
		$time = cutStringInHalf($shift, $delimiter);
		$queryCount = "SELECT COUNT(*) AS count FROM volunteer_shift 
               JOIN shift ON volunteer_shift.shiftId = shift.shiftId 
               WHERE shift.startTime='{$time['startTime']}'";
		$resultCount = $conn->query($queryCount);
		if (!$resultCount) die("Fatal Error: " . mysqli_error($conn));
			
		if ($resultCount->num_rows > 0) {
			// get count
			$row = $resultCount->fetch_assoc();
			$count = htmlspecialchars($row['count']);
			// Calculate the remaining count
			$remainingCount["$shift"] = 5 - $count;

			// Return the count
			return $remainingCount["$shift"];
		} 
	}
		
	$remainingCount = [];
	// reflect the information in the database
	function reflectDatabaseInformation(&$remainingCount, $conn) {
		$shifts = ["6:00pm - 7:00pm", "7:00pm - 8:00pm", "8:00pm - 9:00pm", "9:00pm - 10:00pm", "10:00pm - 11:00pm"];
		foreach ($shifts as $shiftOption) {
			$remainingCount[$shiftOption] = updateRemainingCount($conn, $shiftOption);
		}
		return $remainingCount;
	}
	reflectDatabaseInformation($remainingCount, $conn);

	$errFirstName = $errLastName = $first = $last = $email = $shift = $displayMessage = "";
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		// retrieve data from form
		$first = $_POST['fname'];
		$last = $_POST['lname'];
		$email = $_POST['email'];
		$shift = $_POST['shift'];
		$errorsFName = array();
		$errorsLName = array();
		
			
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
			
		// reflect the information in the database after submission
		reflectDatabaseInformation($remainingCount, $conn);
			
		if (empty($errorsFName) && empty($errorsLName)) {
			$time = cutStringInHalf($shift, "-");

			// query to check for duplicate registration
			$queryCheckDuplicate = "SELECT COUNT(*) AS duplicateCount FROM volunteer_shift
				JOIN shift ON volunteer_shift.shiftId = shift.shiftId
				JOIN volunteers ON volunteer_shift.volunteerId = volunteers.volunteerId
				WHERE volunteers.firstname = '$first' AND volunteers.lastname = '$last' AND volunteers.email = '$email'
				AND shift.startTime = '{$time['startTime']}' AND shift.endTime = '{$time['endTime']}'";
			$resultCheckDuplicate = $conn->query($queryCheckDuplicate);
			if (!$resultCheckDuplicate) die("Fatal Error: " . mysqli_error($conn));
			
			// Retrieve the count of duplicate registrations from the query result
			$row = $resultCheckDuplicate->fetch_assoc();
			$duplicateCount = $row['duplicateCount'];

			// Query to check if the volunteer with the same details already exists
			$querySameVolunteer = "SELECT * FROM volunteers WHERE firstname='$first' AND lastname='$last' AND email = '$email'";
			$resultSameVolunteer = $conn->query($querySameVolunteer);
				
			// Query to check if a shift with the same start time already exists
			$querySameShift = "SELECT * FROM shift WHERE startTime='{$time['startTime']}'";
			$resultSameShift = $conn->query($querySameShift);
			if (!$resultSameShift) die("Fatal Error: " . mysqli_error($conn));
			
			// if volunteer registers for the same shift, display this message
			if ($duplicateCount > 0) {
				$displayMessage = "<b>Duplicate registration not processed. Thanks, " . "$first!<b>";
			} else if ($resultSameVolunteer->num_rows > 0) {
				// if the a volunteer registers to volunteer again, fetch the volunteerId associated with them to use in volunteer_shift
				$rowSameVolunteer = $resultSameVolunteer->fetch_assoc();
				$volunteerId = htmlspecialchars($rowSameVolunteer['volunteerId']);
				
				// Check if a shift with the same start time already exists
				if ($resultSameShift->num_rows > 0) {
					// Query to retrieve the shiftId for the existing shift with the same start time
					$querySameShift = "SELECT shiftId FROM shift WHERE startTime='{$time['startTime']}'";
					$resultSameShift = $conn->query($querySameShift);
					if (!$resultSameShift) die("Fatal Error: " . mysqli_error($conn));
					// Fetch the row containing shiftId from the query result
					$rowSameShift = $resultSameShift->fetch_assoc();
					// Retrieve the shiftId to use in volunteer_shift and sanitize it using htmlspecialchars
					$shiftId= htmlspecialchars($rowSameShift['shiftId']);
				} else {
					// If no shift with the same start time exists, insert a new shift into the 'shift' table
					$query = "INSERT INTO shift (startTime, endTime) VALUES ('{$time['startTime']}', '{$time['endTime']}')";
					$result = $conn->query($query);
					if (!$result) die("Fatal Error: " . mysqli_error($conn));
					// Retrieve the newly inserted shiftId
					$shiftId = $conn->insert_id;	// to use in volunteer_shift
				}
				
				// Insert a new record into the 'volunteer_shift' table
				$query = "INSERT INTO volunteer_shift (volunteerId, shiftId) VALUES ($volunteerId, $shiftId)";
				$result = $conn->query($query);
				if (!$result) die("Fatal Error: " . mysqli_error($conn));
					
				$displayMessage = "<b>Thank you for being so generous with your time, " . "$first!<b>";

			} else if ($resultSameShift->num_rows > 0){
				// insert a new volunteer record into the 'volunteers' table
				$query = "INSERT INTO volunteers (firstname, lastname, email) VALUES ('$first', '$last', '$email')";
				$result = $conn->query($query);
				if (!$result) die("Fatal Error");
				$volunteerId = $conn->insert_id;	// to use in volunteer_shift
				
				// Retrieve the auto-generated ID of the newly inserted volunteer from the 'volunteers' table to use in volunteer_shift
				$rowSameShift = $resultSameShift->fetch_assoc();
				$shiftId= htmlspecialchars($rowSameShift['shiftId']);
				
				// insert a new record into the 'volunteer_shift' table
				$query = "INSERT INTO volunteer_shift (volunteerId, shiftId) VALUES ($volunteerId, $shiftId)";
				$result = $conn->query($query);
				if (!$result) die("Fatal Error: " . mysqli_error($conn));
					
				$displayMessage = "<b>Thank you for registering to volunteer, " . "$first!<b>";
						
			} else {
				// insert a new volunteer record into the 'volunteers' table
				$query = "INSERT INTO volunteers (firstname, lastname, email) VALUES ('$first', '$last', '$email')";
				$result = $conn->query($query);
				if (!$result) die("Fatal Error");
				
				// Retrieve the auto-generated ID of the newly inserted volunteer from the 'volunteers' table to use for volunteer_shift
				$volunteerId = $conn->insert_id;
				
				// insert a new record into the 'shift' table
				$query = "INSERT INTO shift (startTime, endTime) VALUES ('{$time['startTime']}', '{$time['endTime']}')";
				$result = $conn->query($query);
				if (!$result) die("Fatal Error: " . mysqli_error($conn));
				
				// Retrieve the auto-generated ID of the newly inserted shift from the 'shift' table to use in volunteer_shift table
				$shiftId = $conn->insert_id;
					
				// insert a new record into the 'volunteer_shift' table
				$query = "INSERT INTO volunteer_shift (volunteerId, shiftId) VALUES ($volunteerId, $shiftId)";
				$result = $conn->query($query);
				if (!$result) die("Fatal Error: " . mysqli_error($conn));
		
				$displayMessage = "<b>Thank you for registering to volunteer, " . "$first!<b>";	
			}
				
			// clear first and last name, email, and shift if form is submitted and there are no errors
			$first = $last = $email = $shift = "";
				
			// reflect the information in the database
			reflectDatabaseInformation($remainingCount, $conn);
		}
	}
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
		Thank you for your willingness to help to help Salma's Extremely Worthy Cause. 
		Please fill out the form to sign up fo a shift. You may register for more than one slot. 
		Duplicte registrations (for the same slot) will not be processed.
	</p>
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
            // Check if the remaining count for the shift is greater than 0
            if (isset($remainingCount[$shiftOption]) && $remainingCount[$shiftOption] > 0) {
                // Display the option with the updated string
                echo "<option value=\"$shiftOption\">$shiftOption ($remainingCount[$shiftOption] of 5 slots open)</option>";
            } else {
                // If count is 0, disable the option
                echo "<option value=\"$shiftOption\" disabled>$shiftOption: (0 of 5 slots open)</option>";
            }
        } 
		?>
		</select>
		<input type="submit" value="Register" />
		
	</form>
	<p id="displayMessage"><?php echo $displayMessage?></p>
	<?php 		
		// close connection
		$conn->close();
	?>
</body>
</html>