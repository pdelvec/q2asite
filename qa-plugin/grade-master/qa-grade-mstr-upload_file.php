<?php
//********************Upload Sakai CSV file to Sakai folder in plugin*****************************************//
//********************FILE MUST BE A .CSV FILE****************************************************************// 
//*****************AND MUST BE TITLED EXACT SAME NAME OF CATEGORY IN ORDER TO BE FOUND BY FUNCTION************//
  if ($_FILES["file"]["error"] > 0)
    {
    echo "Return Code: " . $_FILES["file"]["error"] . "<br>";
    }
  else
    {
    echo "Upload: " . $_FILES["file"]["name"] . "<br>";
    echo "Type: " . $_FILES["file"]["type"] . "<br>";
    echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
    echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br>";
    if (file_exists($_FILES["file"]["name"]))
      {
      unlink($_FILES["file"]["name"]);
      }
      move_uploaded_file($_FILES["file"]["tmp_name"],
      $_FILES["file"]["name"]);
      echo "Stored in: " .$_FILES["file"]["name"];
    }