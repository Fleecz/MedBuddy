<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Aktivitätenübersicht</title>
  <style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #f5f5f5; }
    .actions a { margin-right: 8px; }
  </style>
</head>
<body>
  <h2>Aktivitätendetails</h2>
  <a href="create.php">neue Aktivität eintragen</a>
  <a href="dashboard.php">Dashboard</a>

  <?php
  require_once "config.php";
  $sql = "SELECT aktivität_id, titel, beschreibung, category FROM aktivität ORDER BY aktivität_id DESC";
  if ($result = mysqli_query($link, $sql)) {
      if (mysqli_num_rows($result) > 0) {

          echo '<table>';
          echo "<thead>";
          echo "<tr>";
          echo "<th>#</th>";
          echo "<th>Titel</th>";
          echo "<th>Beschreibung</th>";
          echo "<th>Kategorie</th>";
          echo "<th>Aktionen</th>";
          echo "</tr>";
          echo "</thead>";
          echo "<tbody>";
          while ($row = mysqli_fetch_assoc($result)) {
              $id = (int)$row['aktivität_id'];
              echo "<tr>";
              echo "<td>" . $id . "</td>";
              echo "<td>" . htmlspecialchars($row['titel'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
              echo "<td>" . htmlspecialchars($row['beschreibung'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
              echo "<td>" . htmlspecialchars($row['category'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
              echo '<td class="actions">';
              echo '<a href="read.php?id=' . $id . '" title="View">Ansehen</a>';
              echo '<a href="update.php?id=' . $id . '" title="Update">Bearbeiten</a>';
              echo '<a href="delete.php?id=' . $id . '" title="Delete" onclick="return confirm(\'Wirklich löschen?\')">Löschen</a>';
              echo "</td>";
              echo "</tr>";
          }
          echo "</tbody>";
          echo "</table>";

          mysqli_free_result($result);
      } else {
          echo '<p><em>No records were found.</em></p>';
      }
  } else {
      echo "<p>Oops! Something went wrong. Please try again later.</p>";
  }

  mysqli_close($link);
  ?>
</body>
</html>