<!DOCTYPE html>
<html lang="es">
<head>
  <!-- Common meta tags -->
  <meta charset="utf-8">
  <meta http-equiv="content-type" content="text/html">
  <title>Chocoflan | <?=$title?></title>
  <meta name="no-email-collection" content="http://www.unspam.com/noemailcollection/">

  <!-- Authors meta tags -->
  <meta name="author" content="Teo Gonzalez @thblckjkr;">
  <meta name="designer" content="Teo Gonzalez @thblckjkr;">
  <meta name="publisher" content="Teo Gonzalez @thblckjkr;">

  <!-- Design optimizations meta -->
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="theme-color" content="#">

  <!-- Bootstrap core CSS -->
  <link href="<?=asset_url()?>css/bootstrap.min.css" rel="stylesheet">

  <!-- Custom fonts for this template -->
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.2/css/all.css"
  integrity="sha384-/rXc/GQVaYpyDdyxK+ecHPVYJSN9bmVFBvjA/9eOB+pb3F2w2N6fc5qB9Ew5yIns"
  crossorigin="anonymous">

  <!-- JQuery -->
  <script src="<?=asset_url()?>js/jquery.min.js"></script>

  <!-- Custom styles for this template -->
  <link href="<?=asset_url();?>css/styles.css" rel="stylesheet">
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top d-print-none" id="main-nav">
  <div class="container">
    <a class="navbar-brand js-scroll-trigger" href="/proyects">
       PPyC
   </a>
    <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive"
    aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
      <i class="fas fa-bars"></i>
    </button>
    <div class="collapse navbar-collapse" id="navbarResponsive">
      <ul class="navbar-nav ml-auto">
      <?php if(@$showmenus !== false): ?>
        <li class="nav-item">
          <a class="nav-link js-scroll-trigger" href="<?=base_url()?>proyects">
            <i class="fa fa-book"></i>
            Proyectos
          </a>
        </li>
        <?php if(!@$_SESSION['validated']):?>
          <li class="nav-item">
            <a class="nav-link js-scroll-trigger" href="<?=base_url()?>account/login">Iniciar sesión</a>
          </li>
        <?php else: ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown"
            aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-user"></i>
              Bienvenid@ <?=$_SESSION['user_name']?>
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
              <a class="dropdown-item" href="<?=base_url()?>account/">
                <i class="fas fa-user"></i>
                Cuenta
              </a>
              <?php if ( $this->session->userdata('user_level') == 1 ): ?>
                <a class="dropdown-item" href="<?=base_url()?>admin/">
                  <i class="fas fa-lock"></i>
                  Administrador
                </a>
              <?php endif; ?>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="<?=base_url()?>account/logout">
                <i class="fas fa-sign-out-alt"></i>
                Cerrar sesión
              </a>
            </div>
          </li>
        <?php endif; ?>
      <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
