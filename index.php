<?php
// Connect to the MySQL database.
$conn = new mysqli("localhost", "root", "", "sdc310_wk3pa");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Process form submissions.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $id = (int) ($_POST["UserNo"] ?? 0);

    $name = trim($_POST["FullName"] ?? "");
    $birthdate = trim($_POST["Birthdate"] ?? "");
    $color = trim($_POST["FavoriteColor"] ?? "");
    $place = trim($_POST["FavoritePlace"] ?? "");
    $nickname = trim($_POST["Nickname"] ?? "");

    // Add a new personal information record.
    if ($action === "add") {
        $stmt = $conn->prepare(
            "INSERT INTO personal_info
            (FullName, Birthdate, FavoriteColor, FavoritePlace, Nickname)
            VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssss",
            $name, $birthdate, $color, $place, $nickname);
        $stmt->execute();
        $stmt->close();
    }

    // Update an existing record using its primary key.
    if ($action === "update" && $id > 0) {
        $stmt = $conn->prepare(
            "UPDATE personal_info SET
            FullName=?, Birthdate=?, FavoriteColor=?,
            FavoritePlace=?, Nickname=? WHERE UserNo=?"
        );
        $stmt->bind_param("sssssi",
            $name, $birthdate, $color, $place, $nickname, $id);
        $stmt->execute();
        $stmt->close();
    }

    // Delete the selected record.
    if ($action === "delete" && $id > 0) {
        $stmt = $conn->prepare(
            "DELETE FROM personal_info WHERE UserNo=?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    // Prevent duplicate submissions when refreshing.
    header("Location: index.php");
    exit;
}

// Retrieve all stored personal information.
$result = $conn->query(
    "SELECT * FROM personal_info ORDER BY UserNo"
);

// Escape displayed values for safe HTML output.
function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lucas Justiniano Wk 3 Performance Assessment</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 30px;
        }

        h1, h2 {
            color: #243b55;
        }

        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        input {
            width: 100%;
            max-width: 450px;
            padding: 9px;
            margin: 5px 0;
            box-sizing: border-box;
        }

        button {
            padding: 10px 18px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            background-color: #243b55;
            color: white;
            cursor: pointer;
        }

        .delete {
            background-color: #b83232;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        th, td {
            padding: 12px;
            border: 1px solid #cccccc;
            text-align: left;
        }

        th {
            background-color: #243b55;
            color: white;
        }

        .inline-form {
            display: inline;
            padding: 0;
            margin: 0;
        }
    </style>
</head>

<body>

<h1>Lucas Justiniano Wk 3 Performance Assessment</h1>

<h2>Personal Information Form</h2>

<!-- Form for adding and updating personal information. -->
<form method="post" id="personalForm">

    <input type="hidden" name="UserNo" id="UserNo">

    <label for="FullName">Name:</label>
    <input type="text" name="FullName" id="FullName"
           maxlength="50" required>

    <label for="Birthdate">Date of Birth:</label>
    <input type="text" name="Birthdate" id="Birthdate"
           placeholder="MM/DD/YYYY" maxlength="25" required>

    <label for="FavoriteColor">Favorite Color:</label>
    <input type="text" name="FavoriteColor" id="FavoriteColor"
           maxlength="50" required>

    <label for="FavoritePlace">Favorite Place To Visit:</label>
    <input type="text" name="FavoritePlace" id="FavoritePlace"
           maxlength="50" required>

    <label for="Nickname">Nickname:</label>
    <input type="text" name="Nickname" id="Nickname"
           maxlength="25" required>

    <br>

    <button type="submit" name="action" value="add">
        Add Record
    </button>

    <button type="submit" name="action" value="update"
            id="updateButton" disabled>
        Update Record
    </button>

    <button type="button" onclick="clearForm()">
        Clear Form
    </button>

</form>

<h2>Stored Personal Information</h2>

<!-- Display the database records in an HTML table. -->
<table>
    <tr>
        <th>User No</th>
        <th>Name</th>
        <th>Date of Birth</th>
        <th>Favorite Color</th>
        <th>Favorite Place To Visit</th>
        <th>Nickname</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= escape($row["UserNo"]) ?></td>
            <td><?= escape($row["FullName"]) ?></td>
            <td><?= escape($row["Birthdate"]) ?></td>
            <td><?= escape($row["FavoriteColor"]) ?></td>
            <td><?= escape($row["FavoritePlace"]) ?></td>
            <td><?= escape($row["Nickname"]) ?></td>

            <td>
                <button type="button"
                    data-record="<?= escape(json_encode($row)) ?>"
                    onclick="editRecord(JSON.parse(this.dataset.record))">
                    Edit
                </button>

                <form method="post" class="inline-form"
                      onsubmit="return confirm('Delete this record?');">

                    <input type="hidden" name="UserNo"
                           value="<?= escape($row["UserNo"]) ?>">

                    <button type="submit" name="action"
                            value="delete" class="delete">
                        Delete
                    </button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>

</table>

<script>
    // Populate the form with the selected record.
    function editRecord(record) {
        document.getElementById("UserNo").value = record.UserNo;
        document.getElementById("FullName").value = record.FullName;
        document.getElementById("Birthdate").value = record.Birthdate;
        document.getElementById("FavoriteColor").value = record.FavoriteColor;
        document.getElementById("FavoritePlace").value = record.FavoritePlace;
        document.getElementById("Nickname").value = record.Nickname;
        document.getElementById("updateButton").disabled = false;

        window.scrollTo(0, 0);
    }

    // Clear the form and reset the selected record.
    function clearForm() {
        document.getElementById("personalForm").reset();
        document.getElementById("UserNo").value = "";
        document.getElementById("updateButton").disabled = true;
    }
</script>

</body>
</html>

<?php
// Close the database connection.
$conn->close();
?>
