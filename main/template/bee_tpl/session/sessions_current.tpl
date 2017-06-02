<section id="sessions-current">
    {% if hot_sessions %}
            <div class="row">
                <!-- Esto repite para mostar 8 sessiones recientes -->
                {% for session in hot_sessions %}
                    <div class="col-xs-12 col-sm-4 col-md-3">
                        <div class="item-session">
                            <div class="thumbnail">
                                <a href="{{ _p.web }}session/{{ session.id }}/about/" title="title-session"><img src="{{ _p.web_upload }}{{ session.image }}" id="session-idsession"></a>
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
                                    {{ session.description }}
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
            <!-- Fin de 8 sessiones recientes -->
            </div>
        {% endif %}
</section>
