<?php
require_once ("../classes/init.php");
$pageTitle = "Students";


?>
<body>
  <?php
  include (ROOT_PATH . 'inc/head.php'); 
  include (ROOT_PATH . 'inc/header.php');
  include (ROOT_PATH . 'inc/navbar.php');
  ?>

  <div class="main">
    <div class="container section">
      <div class="wrapper">
        <h2>Students list</h2>
        <div class="pagination">
          <?php// include (ROOT_PATH . "inc/navigation.php"); ?>
        </div>
<?php
            $students = StudentInfo::find_all_students();
            $users=array();
            foreach ($students as $student) {
                $users[] = Student::find_by_id($student->id);
            }
            $student = new Student();
?>
        <ul class="students">

         <?php
         $out = array();
            foreach ($users as $key => $value){
                $out[] = (object)array_merge((array)$students[$key], (array)$value);
            }
            $users = $out;

         foreach ($users as $user) {
            if (Student::find_by_id($user->id)) {
              $faculty = $student->get_faculty($user->faculty_id);
              $faculty = ucwords(str_replace("_", " ", $faculty));

              $output = "";
                $output = $output . "<li>";
                $output = $output .  "Username: " . $user->username . "<br>";
                $output = $output .  "Full name: " . $student->full_name_by_id($user->id) . "<br>";
                $output = $output .  "ID: " . $user->id . "<br>";
                $output = $output .  "Faculty: " . " " . $faculty . "<br><br>";
                $output = $output . "<a href=" . BASE_URL . "students/" . $user->id . "/>View profile</a>";
                $output = $output .  "</li>";
                echo $output;
            }
         }

        ?>
      </ul>
      <div class="pagination">
        <?php //include (ROOT_PATH . "inc/navigation.php"); ?>
      </div>
    </div>
  </div>
</div>

<?php include (ROOT_PATH . 'inc/footer.php') ?>
</body>
</html>