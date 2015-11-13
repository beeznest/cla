<nav class="navbar navbar-default">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#menuone" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="menuone">
            <ul class="nav navbar-nav">
                <li><a href="{{ _p.web }}">Aprende</a></li>
                <li><a href="#">Enseña</a></li>
                <li><a href="#">Comunidad</a></li>
                <li><a href="#">Blog</a></li>
                {{ menu }}
            </ul>
           
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>
