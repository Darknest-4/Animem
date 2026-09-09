<!doctype html>
  <html lang="hu">
    <head>
      <!-- Required meta tags -->
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">

      <!-- Bootstrap CSS -->
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
      <link href="<?= BASEURL;?>Assets/fontawesome/free/5.15.2/css/all.css" rel="stylesheet">
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
  <!-- Option 1: Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

  <!-- Option 2: Separate Popper and Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
  

  <style>
  .hidden{display:none;}
  .block{display: block;}
</style>

      <title>Home</title>

      <style>
          
        .list tr
        {
          background-color: antiquewhite;
        }
        
        .list tr:first-child
        {
          background-color: burlywood;
        }
        .list tr:nth-child(2n)
        {
          background-color: darkseagreen;
        }
        
        .list tr:nth-child(3n) + tr
        {
          background-color: burlywood;
        }

        .scroll
        {	
          scrollbar-color: #444 rgba(0,0,0,0.3);
          scrollbar-width: thin;
          overflow-x: hidden;
          overflow-y: auto;
        }
        .scroll::-webkit-scrollbar
        {
          width: 10px;
          background-color: #F5F5F5;
        }
        .scroll::-webkit-scrollbar-thumb{
          -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,.3);
          background-color: #222;
        }
        .scroll::-webkit-scrollbar-track{
          -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,.3);
          background-color: #F5F5F5;
        }
      </style>
      <style>
          .switch {
            position: relative;
            display: inline-block;
            vertical-align: top;
            width: 100px;
            height: 40px;
            padding: 3px;
            margin: 0 5px 5px 0;
            background: linear-gradient(to bottom, #eeeeee, #FFFFFF 25px);
            background-image: -webkit-linear-gradient(top, #eeeeee, #FFFFFF 25px);
            border-radius: 18px;
            box-shadow: inset 0 -1px white, inset 0 1px 1px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            box-sizing:content-box;
          }
          .switch-input {
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            box-sizing:content-box;
          }
          .switch-label {
            position: relative;
            display: block;
            height: inherit;
            font-size: 24px;
            background: #eceeef;
            border-radius: inherit;
            box-sizing:content-box;
          }
          .switch-label:before, .switch-label:after {
            position: absolute;
            top: 50%;
            -webkit-transition: inherit;
            -moz-transition: inherit;
            -o-transition: inherit;
            transition: inherit;
            box-sizing:content-box;
          }
          .switch-label:before {
            content: attr(data-off);
            right: 11px;
            color: #523e3e;
          }
          .switch-label:after {
            content: attr(data-on);
            left: 11px;
            color: #FFFFFF;
            opacity: 0;
          }
          .switch-input:checked ~ .switch-label {
            background: #E1B42B;
          }
          .switch-input:checked ~ .switch-label:before {
            opacity: 0;
          }
          .switch-input:checked ~ .switch-label:after {
            opacity: 1;
          }
          .switch-handle {
            position: absolute;
            top: 6px;
            left: 6px;
            width: 28px;
            height: 28px;
            background: linear-gradient(to bottom, #FFFFFF 40%, #f0f0f0);
            background-image: -webkit-linear-gradient(top, #FFFFFF 40%, #f0f0f0);
            border-radius: 100%;
          }
          .switch-handle:before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            margin: -6px 0 0 -6px;
            width: 12px;
            height: 12px;
            background: linear-gradient(to bottom, #eeeeee, #FFFFFF);
            background-image: -webkit-linear-gradient(top, #eeeeee, #FFFFFF);
            border-radius: 6px;
          }
          .switch-input:checked ~ .switch-handle {
            left: 74px;
          }
          
          /* Transition
          ========================== */
          .switch-label, .switch-handle {
            transition: All 0.3s ease;
            -webkit-transition: All 0.3s ease;
            -moz-transition: All 0.3s ease;
            -o-transition: All 0.3s ease;
          }
          /* Sw itch Slide
          ==========================*/
          .switch-slide {
            padding: 0;
            margin: 0px 0 0;
            background: #eceeef;
            border-radius: 0;
            background-image: none;
          }
          .switch-slide .switch-label {
            box-shadow: none;
            background: none;
            overflow: hidden;
          } 
          .switch-slide .switch-label:after, .switch-slide .switch-label:before {
            width: 100%;
            height: 100%;
            top: 0px;
            left: 0;
            text-align: center;
          }
          .switch-slide .switch-label:after {
            color: #FFFFFF;
            background: #181;
            left: -100px;
          }
          .switch-slide .switch-label:before {
            background: #eceeef;
          }
          .switch-slide .switch-handle {
            display: none;
          }
          .switch-slide .switch-input:checked ~ .switch-label {
            background: #181;
            border-color: #0088cc;
          }
          .switch-slide .switch-input:checked ~ .switch-label:before {
            left: 100px;
          }
          .switch-slide .switch-input:checked ~ .switch-label:after {
            left: 0;
          }
      </style>
      
    </head>
    <body class="scroll">
      <div class="container-fluid mt-3">
        <div class="row mx-0">
          <div class="col-12 px-0">
            <nav class="navbar navbar-expand-lg navbar-light bg-light mt-0">
              <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTogglerDemo03" aria-controls="navbarTogglerDemo03" aria-expanded="false" aria-label="Toggle navigation">
                  <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand">Admin</a>
                <div class="collapse navbar-collapse" id="navbarTogglerDemo03">
                  <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                      <a class="nav-link active" aria-current="page" href="<?=  BASEURL; ?>episode.php">
                        EpisodeList
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link active" aria-current="page" href="<?=  BASEURL; ?>uploaders.php">
                        Uploaders
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link active" aria-current="page" href="<?=  BASEURL; ?>datasheet.php">
                        DataSheet
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link active" aria-current="page" href="<?=  BASEURL; ?>blog.php">
                        Blog
                      </a>
                    </li>
                    <li class="nav-item me-2">
                      <form action="/episode.php" method="get" id="formsearch">
                        <div class="input-group">
                          <input type="text" class="form-control" name="search" placeholder="Search" form="formsearch" value="<?= '' . $search . '';?>">
                          <button class="input-group-text" form="formsearch" type="submit">Search</button>
                        </div>
                      </form>
                    </li>
                    <li class="nav-item me-2">
                      <form action="/episode.php" method="get" id="formnew">
                        <div class="input-group">
                          <input type="text" class="form-control" name="new" placeholder="New EpisodeList" form="formnew">
                          <button class="input-group-text" form="formnew" type="submit">New</button>
                        </div>
                      </form>
                    </li>
                  </ul>
                </div>
              </div>
            </nav>
          </div>


