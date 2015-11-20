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
                                <span class="category">{{ session.name }}</span>
                            </span>
                        </div>
                        <div class="description">
                            <div class="title">
                                <h3><a href="vinculoalaaboutsession" title="title-session">{{ session.category_name }}</a></h3>
                                <div class="teacher">{{ session.firstname }} {{ session.lastname }}</div>
                            </div>
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
