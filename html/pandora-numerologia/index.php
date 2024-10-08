

<!DOCTYPE html>
<html lang="en">


    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    
        <title>Pandora Numerologia</title>

    
        <meta name="description" content="Pandora Numerologia">
        <meta name="keywords" content="Pandora Numerologia">
        <meta name="author" content="Paula Pinto">
        <link rel="icon" href="assets/media/logo.png" type="image/x-icon">
        <link rel="shortcut icon" href="assets/media/logo.png" type="image/x-icon">
    
        <meta property="og:title" content="Pandora Numerologia">
        <meta property="og:description" content="Descubra Seu Número">
        <meta property="og:image" content="https://kickback-kingdom.com/pandora-numerologia/assets/media/logo.png">
        <meta property="og:url" content="https://kickback-kingdom.com/pandora-numerologia/">
        <meta name="twitter:card" content="summary_large_image">

        <!-- Bootstrap CSS -->
        <link href="assets/vendors/bootstrap/bootstrap.min.css" rel="stylesheet">
        <!-- Basic stylesheet -->

        <!-- Default Theme -->
        <script src="https://kit.fontawesome.com/f098b8e570.js" crossorigin="anonymous"></script>

    
        <link rel="stylesheet" type="text/css" href="assets/css/style.css">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prettify/r298/prettify.min.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.css" rel="stylesheet" />
    </head>
    <body class="bg-body-secondary container p-0">
    
        <!--LOADING OVERLAY-->
        <div id="loading-overlay">
            <img src="assets/media/logo.png" alt="Loading..." class="fa-bounce" style="/*mix-blend-mode: multiply;*/" />
        </div>

        <!-- ERROR MODAL -->
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header text-bg-danger">
                        <h1 class="modal-title fs-5" id="errorModalLabel">Modal title</h1>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="errorModalMessage"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn bg-ranked-1" data-bs-dismiss="modal">Okay</button>
                    </div>
                </div>
            </div>
        </div> 

        <!-- SUCCESS MODAL -->
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="successModalLabel">Modal title</h1>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="successModalMessage"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn bg-ranked-1" data-bs-dismiss="modal">Okay</button>
                    </div>
                </div>
            </div>
        </div>

  
        <!-- Modal -->
        <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="staticBackdropLabel">Inserir Seus Dados</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="fname" class="form-label">Nome de batismo completo</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                <input type="text" class="form-control" id="fname" aria-describedby="basic-addon3 basic-addon4">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="dob" class="form-label">Data de nascimento</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-calendar-days"></i></span>
                                <input type="date" class="form-control" id="dob" aria-describedby="basic-addon3 basic-addon4">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="houseNum" class="form-label">Número da sua casa ou imóvel</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-house"></i></span>
                                <input type="text" class="form-control" id="houseNum" aria-describedby="basic-addon3 basic-addon4">
                            </div>
                        </div>
                        <!--<div class="mb-3">
                            <label for="profName" class="form-label">Nome profissional ou nome e sobrenome preferidos</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-briefcase"></i></span>
                                <input type="text" class="form-control" id="profName" aria-describedby="basic-addon3 basic-addon4">
                            </div>
                        </div>-->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sair</button>
                        <button type="button" class="btn btn-primary" onclick="DoCalculations()">Calcule</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="calculationResult" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="calculationResultLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="calculationResultLabel">Resultado</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="accordion" id="accordionPanelsStayOpenExample">
                
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseSix" aria-expanded="false" aria-controls="panelsStayOpen-collapseSix">
                                        Alma<span id="finalResultName" class="badge bg-primary position-absolute rounded-pill" style="right: 48px;">*</span>
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseSix" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                            
                                        <ol class="list-group list-group-numbered" id="resultsName">
                                
                                        </ol>

                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseSeven" aria-expanded="false" aria-controls="panelsStayOpen-collapseSeven">
                                    Aparência<span id="finalResultNameCon" class="badge bg-primary position-absolute rounded-pill" style="right: 48px;">*</span>
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseSeven" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                            
                                        <ol class="list-group list-group-numbered" id="resultsNameCon">
                                
                                        </ol>

                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                                      Destino<span id="finalResultDestiny" class="badge bg-primary position-absolute rounded-pill" style="right: 48px;">*</span>
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse">
                                    <div class="accordion-body" id="resultsDestiny">
                      

                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
                                    Lição de vida<span id="finalResultLifeLessons" class="badge bg-primary position-absolute rounded-pill" style="right: 48px;">*</span>
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse">
                                    <div class="accordion-body"id="resultsLifeLessons">
                      
                        
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseThree" aria-expanded="false" aria-controls="panelsStayOpen-collapseThree">
                                    Número poderoso<span id="finalResultPowerfulNumber" class="badge bg-primary position-absolute rounded-pill" style="right: 48px;">*</span>
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse">
                                    <div class="accordion-body" id="resultsPowerfulNumber">
                      
                        
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseFour" aria-expanded="false" aria-controls="panelsStayOpen-collapseFour">
                                    Desafio(s)
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseFour" class="accordion-collapse collapse show">
                                    <div class="accordion-body"id="resultsLifeChallenges">
                      
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseAnoPessoal" aria-expanded="false" aria-controls="panelsStayOpen-collapseAnoPessoal">
                                    Ano Pessoal<span id="finalResultAnoPessoal" class="badge bg-primary position-absolute rounded-pill" style="right: 48px;">*</span>
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseAnoPessoal" class="accordion-collapse collapse">
                                    <div class="accordion-body"id="resultsAnoPessoal">
                      
                                    </div>
                                </div>
                            </div>
                            
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseMesPessoal" aria-expanded="false" aria-controls="panelsStayOpen-collapseMesPessoal">
                                    Mês Pessoal<span id="finalResultMesPessoal" class="badge bg-primary position-absolute rounded-pill" style="right: 48px;">*</span>
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseMesPessoal" class="accordion-collapse collapse">
                                    <div class="accordion-body"id="resultsMesPessoal">
                      
                                    </div>
                                </div>
                            </div>
                            
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseEight" aria-expanded="false" aria-controls="panelsStayOpen-collapseEight">
                                    Dia Pessoal<span id="finalResultDiaPessoal" class="badge bg-primary position-absolute rounded-pill" style="right: 48px;">*</span>
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseEight" class="accordion-collapse collapse">
                                    <div class="accordion-body"id="resultsDiaPessoal">
                      
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseFive" aria-expanded="false" aria-controls="panelsStayOpen-collapseFive">
                                        Pináculos/Fases da vida
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseFive" class="accordion-collapse collapse show">
                                    <div class="accordion-body" id="resultsPhasesOfLife">
                            
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-secondary mt-2 pb-0 pt-2" role="alert">
                            <dl class="mb-0 pb-0 row">
                                <dt class="">Nome de batismo completo</dt>
                                <dd class="" id="result_display_name"></dd>
                                <dt class="">Data de nascimento</dt>
                                <dd class="" id="result_display_birth"></dd>
                                <dt class="">Número da sua casa ou imóvel</dt>
                                <dd class="" id="result_display_address"></dd>
                                <!--<dt class="">Nome profissional ou nome e sobrenome preferidos</dt>
                                <dd class="" id="result_display_prof_name"></dd>-->

                            </dl>
                        </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Sair</button>
                    </div>
                </div>
            </div>
        </div>

        <!--MOBILE TOP BAR-->
        <nav class="d-md-none d-sm-block fixed-top navbar bg-primary" data-bs-theme="dark">
            <div class="container-fluid">
                <a class="btn btn-primary" href="https://www.youtube.com/@pandoranumerologia" target="_blank">
                    <i class="fa-brands fa-youtube"></i>
                </a>
                <a class="me-0 me-lg-2 navbar-brand p-0 mobile-navbar-logo" href="/pandora-numerologia/" aria-label="Bootstrap">
                    <img class="kk-logo" src="assets/media/logo.png" />
                </a>

                <a class="btn btn-primary" href="https://www.instagram.com/pandoranumerologia/" target="_blank">
                    <i class="fa-brands fa-instagram"></i>
                </a>
            </div>
        </nav>

        <!--DESKTOP NAVBAR-->
        <nav class="container d-md-block d-sm-none d-none fixed-top navbar navbar-expand bg-primary" aria-label="Second navbar example" data-bs-theme="dark">
            <div class="container">
                <a class="navbar-brand kk-logo-desktop" href="/pandora-numerologia/">
                    <img class="kk-logo" src="assets/media/logo.png" />
                    Pandora Numerologia
                </a>
                <div class="collapse navbar-collapse" id="navbarsExample02">
                    <ul class="navbar-nav me-auto">
                
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="btn btn-primary" type="button" href="https://www.youtube.com/@pandoranumerologia">
                                <i class="fa-brands fa-youtube"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary" type="button" href="https://www.instagram.com/pandoranumerologia/">
                                <i class="fa-brands fa-instagram"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!--TOP AD-->
        <div class="d-none d-md-block w-100 ratio d-flex align-items-center justify-content-center" style="--bs-aspect-ratio: 26%; margin-top: 56px; position: relative;">

            <!-- Image -->
            <img src="assets/media/dalle-banner.jpg" class="position-absolute top-0 start-0 w-100 h-100">

            <!-- Text -->
            <div class="text-center">
                <span class="d-md-block d-none h3 pt-5 text-shadow text-white"></span>
            </div>

        </div>
        <div class="d-block d-md-none w-100 ratio d-flex align-items-center justify-content-center" style="margin-top: 56px; --bs-aspect-ratio: 46.3%; position: relative;">

            <!-- Image -->
            <img src="assets/media/dalle-banner2.jpg" class="position-absolute top-0 start-0 w-100 h-100">

            <!-- Text -->
            <div class="text-center">
                <span class="d-block h5 pt-5 text-shadow text-white"></span>
            </div>

        </div>


        <!--MAIN CONTENT-->
        <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
            <div class="row">
                <div class="col-12">
                    <div class="card mb-3 bg-primary"  data-bs-theme="dark">
                        <div class="border-0 card-header">
                            <h5 class="card-title mb-0">
                                <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
                                    <ol class="breadcrumb mb-0">
                                        <li class="breadcrumb-item active" aria-current="page">Bem-vindos</li>
                                    </ol>
                                </nav>
                            </h5>
                        </div>
                    </div>
                    <div class="row flex-lg-row-reverse align-items-center g-5  px-3">
                        <div class="col-lg-6">
                            <div class="ratio ratio-16x9">
                                <iframe src="https://www.youtube.com/embed/9917l7AzhHA?rel=0" title="YouTube video" allowfullscreen=""></iframe>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <h1 class="display-6 fw-bold text-body-emphasis lh-1 mb-3" style="text-align: center;">SE VOCÊ NÃO TEM UM CAMINHO, A PANDORA NUMEROLOGIA TEM UM CAMINHO PARA VOCÊ!</h1>
                            <p class="lead" style="text-align: justify;">Descubra o poder dos números com  a nossa ferramenta de Numerologia. </p>
                            <p class="lead" style="text-align: justify;">Ao inserir os seus dados de batismo no formulário a seguir, você poderá descobrir o seu mapa Numerológico e depois acessar os vídeos com os resultados nas playlists do nosso canal do YouTube!</p>
                            <p class="lead" style="text-align: justify;">Experimente agora e descubra o Poder que há em você!</p>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                                <button type="button" class="btn btn-primary px-4 me-md-2" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><i class="fa-solid fa-list-ol"></i> Faça o seu mapa aqui</button>
                                <a class="btn btn-primary px-4 me-md-2" href="https://a.co/d/7s47cCA" target="_BLANK"><i class="fa-solid fa-book"></i> Compre o seu E-book aqui!</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
                <div class="col-md-4 d-flex align-items-center">
                    <a href="/pandora-numerologia/" class="mb-3 me-2 mb-md-0 text-body-secondary text-decoration-none lh-1">
                    <svg class="bi" width="30" height="24"><use xlink:href="#bootstrap"></use></svg>
                    </a>
                    <span class="mb-3 mb-md-0 text-body-secondary">© 2024 Kickback Kingdom</span>
                </div>
        
                <ul class="nav col-md-4 justify-content-end list-unstyled d-flex">
                    <li class="ms-3"><a class="text-body-secondary" href="https://www.youtube.com/@pandoranumerologia"><i class="fa-brands fa-youtube"></i></a></li>
                    <li class="ms-3"><a class="text-body-secondary" href="https://www.instagram.com/pandoranumerologia/"><i class="fa-brands fa-instagram"></i></a></li>
                </ul>
            </footer>
        </main>

    
        <!-- Optional JavaScript -->
        <!-- jQuery first, then Popper.js, then Bootstrap JS -->
        <script src="assets/vendors/jquery/jquery-3.7.0.min.js"></script>
        <script src="assets/vendors/bootstrap/bootstrap.bundle.min.js"></script>
        <?php
    $jsVersion = time(); // Get current time as a Unix timestamp in PHP
?>

<script src="assets/js/formula.js?v=<?= $jsVersion ?>"></script>

        <script>

        
            $(document).ready(function () {

            
            });


            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

            const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
            const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

            function ShowPopSuccess(message, title)
            {
                $("#successModalLabel").text(title);
                $("#successModalMessage").text(message);
                $("#successModal").modal("show");
                console.log(message);
            }
            function ShowPopError(message, title)
            {
                $("#errorModalLabel").text(title);
                $("#errorModalMessage").text(message);
                $("#errorModal").modal("show");
                console.log(message);
            
            }

        </script>


        <script>

        $(window).on('load', function() {
            $('#loading-overlay').fadeOut('slow', function() {
                $('body').addClass('body-finished-loading');  // add class to restore scrolling
            });
        });

        $('a').click(function(event) {
            var href = $(this).attr('href');

            // Check if href is valid and not just a placeholder like '#'
            if (href && href != '#' && !href.startsWith('#')) {
                event.preventDefault();  // prevent the default action

                $('body').css('overflow', 'hidden');  // prevent scrolling

                $('#loading-overlay').fadeIn('slow', function() {
                    // when the fade-in is complete, navigate to the new page
                    window.location.href = href;
                });
            }
        });



        </script>
    </body>

</html>
