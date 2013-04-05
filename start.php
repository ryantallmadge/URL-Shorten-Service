<!doctype html> 
<head>
	<meta charset="utf-8" />

  </head>
<body>
    <?php if(isset($error)) echo '<span style="color:red">'.$error.'</span>';?>
    <form action="/" method="post">
        <p class="input">
            <input type="text" class="text" name="site" value="<?php if(isset($site) AND $site != '/') echo $site;?>" placeholder="http://">
        </p>      
        <p class="input">
            <input type="submit" name="submit" value="sqez.me" class="submit">
        </p>
    </form>

</body>
</html>


