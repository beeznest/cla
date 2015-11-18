{% if not session.show_simple_session_info %}
        <div class="col-md-3">
            <div class="item-session">
                {% for field_value in session.extra_fields %}
                <div class="thumbnail">
                    {% if field_value.field.variable == 'image' %}
                        <img id="session_img_{{ session.id }}" src="{{ _p.web_upload ~ field_value.value }}"/>
                    {% else %}
                    
                    {% endif %}
                </div>
                 {% endfor %}
                <div class="description">
            {% if session.show_link_to_session %}
                {% if session.courses|length == 1 %}
                    {% set course = session.courses|first %}
                        <h3><a href="{{ course.link }}" alt="{{ session.title }}" title="{{ session.title }}">{{ session.title }}</a></h3>
                    {% else %}
                        <h3><a href="{{ _p.web_main ~ 'session/index.php?session_id=' ~ session.id }}" alt="{{ session.title }}" title="{{ session.title }}">{{ session.title }}</a></h3>
                {% endif %}
            {% else %}
                <h3>{{ session.title }}</h3>
            {% endif %}
                {{ session.description }}
                </div>
                <div class="info">
                    <a alt="{{ session.title }}" title="{{ session.title }}" href="{{ _p.web_main ~ "session/resume_session.php?id_session=" ~ session.id }}">
                        <i class="fa fa-pencil"></i> {{ "Edit"|get_lang }}
                    </a>
                </div>
            </div>
        </div>
{% endif %}