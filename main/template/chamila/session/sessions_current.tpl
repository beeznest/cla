<section id="sessions-current">
    {% if hot_sessions %}
        {% for session in hot_sessions %}
            <div class="row">
                <!-- Esto repite para mostar 8 sessiones recientes -->
                <div class="col-xs-6 col-sm-4 col-md-3">
                    <div class="item-session">
                        <div class="thumbnail">
                            <img src="" id="session-idsession">
                            <span class="course-metadata">
                                <span class="category">{{ session.category_name }}</span>
                            </span>
                        </div>
                        <div class="description">
                            <div class="title">
                                <h3><a href="{{ _p.web }}session/{{ session.id }}/about/" title="title-session">{{ session.name }}</a></h3>
                                
                            </div>
                            <div class="teacher"><i class="fa fa-graduation-cap"></i> {{ session.firstname }} {{ session.lastname }}</div>
                            <div class="text">
                                <p>{{ session.description }}</p>
                            </div>
                            <div class="info">
                                <div class="col-xs-6 col-md-6">
                                    <i class="fa fa-user"></i> {{ session.users }}
                                </div>
                                <div class="col-xs-6 col-md-6">
                                    <i class="fa fa-book"></i> {{ session.lessons }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            {% endfor %}
        {% endif %}
         <!-- Fin de 8 sessiones recientes -->
    </div>
</section>
