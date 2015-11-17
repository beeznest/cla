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
                {% for item in menu %}
                    {% if item['key'] != 'agenda' and item['key'] != 'my-space' and item['key'] != 'profile' and item['key'] != 'dashboard' %}
                        <li class="{{ item['current'] }} {{ item['key'] }}"><a href="{{ item['url'] }}">{{ item['title'] }}</a></li>
                    {% endif %}    
                {% endfor %}
            </ul>
           
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>
