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
                
                <li class="{{ section_name == 'section-mycampus' ? 'active' : '' }}"><a href="{{ _p.web }}">Inicio</a></li>
                <li class="{{ section_name == 'section-mycourses' ? 'active' : ''}}"><a href="{{ _p.web_main }}auth/courses.php" >Aprende</a></li>
                
                {% if _u.logged == 1 %}
                <li class="{{ section_name == 'section-userportal' ? 'active' : ''}}"><a href="{{ _p.web }}user_portal.php" >Mis cursos</a></li>
                <li class="{{ section_name == 'section-social-network' ? 'active' : ''}}"><a href="{{ _p.web_main }}social/home.php" >Comunidad</a></li>
                {% endif %}
                
                {% if _u.logged == 0 %}
                <li class="{{ section_name == 'section-userportal' ? 'active' : ''}}"><a href="#" >Enseña</a></li>
                {% endif %}
                
               
                {% for item in menu %}
                    {% if item['target'] == '_self' %}    
                        <li class="{{item['current']}}"><a href="{{item['url']}}">{{item['title']}}</a></li>
                    {% endif %}    
                {% endfor %}
            </ul>
           
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>
