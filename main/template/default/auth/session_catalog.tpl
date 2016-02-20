{% extends template ~ "/layout/main.tpl" %}

{% block body %}
    <script type="text/javascript">
        $(document).ready(function () {
            $('#date').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        });
    </script>

    <div class="col-md-12">
        <div class="row">
            {% if show_courses %}
                <div class="col-md-4">
                    <div class="section-title-catalog">{{ 'Courses'|get_lang }}</div>
                    {% if not hidden_links %}
                        <form class="form-horizontal" method="post" action="{{ course_url }}">
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <input type="hidden" name="sec_token" value="{{ search_token }}">
                                    <input type="hidden" name="search_course" value="1" />
                                    <div class="input-group">
                                        <input type="text" name="search_term" class="form-control" />
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="submit"><em class="fa fa-search"></em> {{ 'Search'|get_lang }}</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    {% endif %}
                </div>
            {% endif %}

            <div class="col-md-8">
                {% if show_sessions %}
                    <div class="section-title-catalog">{{ 'Sessions'|get_lang }}</div>

                    <div class="row">
                        <div class="col-md-6">
                            <form class="form-horizontal" method="post" action="{{ _p.web_self }}?action=display_sessions">
                                <div class="form-group">
                                    <label class="col-sm-3">{{ "ByDate"|get_lang }}</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <input type="date" name="date" id="date" class="form-control" value="{{ search_date }}" readonly>
                                            <span class="input-group-btn">
                                                <button class="btn btn-default" type="submit"><em class="fa fa-search"></em> {{ 'Search'|get_lang }}</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form class="form-horizontal" method="post" action="{{ _p.web_self }}?action=search_tag">
                                <div class="form-group">
                                    <label class="col-sm-4">{{ "ByTag"|get_lang }}</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" name="search_tag" class="form-control" value="{{ search_tag }}" />
                                            <span class="input-group-btn">
                                                <button class="btn btn-default" type="submit"><em class="fa fa-search"></em> {{ 'Search'|get_lang }}</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                {% endif %}
            </div>
        </div>
    </div>

    <section id="session-list">
        <div class="col-md-12">
            <div class="row">
                {% for session in sessions %}
                    <div class="col-md-3 session-col">
                        <div class="item" id="session-{{ session.id }}">
                            <img src="{{ session.image ? _p.web_upload ~ session.image : _p.web_img ~ 'session_default.png' }}">

                            <div class="information-item">
                                <h3 class="title-session">
                                    <a href="{{ _p.web ~ 'session/' ~ session.id ~ '/about/' }}" title="{{ session.name }} " class="{{ session.services ? 'serviceCheckFont' : '' }}">
                                        {{ session.name }} {{ session.services ? '<em class="fa fa-diamond"></em>' : '' }}
                                    </a>
                                </h3>
                                <ul class="list-unstyled">
                                    {% if show_tutor %}
                                        <li class="author-session">
                                            <em class="fa fa-user"></em> {{ session.coach_name }}
                                        </li>
                                    {% endif %}
                                    <li class="date-session">
                                        <em class="fa fa-calendar-o"></em> {{ session.date }}
                                    </li>
                                    {% if session.tags %}
                                        <li class="tags-session">
                                            <em class="fa fa-tags"></em> {{ session.tags|join(', ')}}
                                        </li>
                                    {% endif %}
                                </ul>

                                <div class="options">
                                    {% if not _u.logged %}
                                        <p>
                                            <a class="btn btn-info btn-block btn-sm" href="{{ "#{_p.web}session/#{session.id}/about/" }}" title="{{ session.name }}">{{ 'SeeCourseInformationAndRequirements'|get_lang }}</a>
                                        </p>
                                    {% else %}
                                        <p>
                                            <a class="btn btn-info btn-block btn-sm" role="button" data-toggle="popover" id="session-{{ session.id }}-sequences">{{ 'SeeSequences'|get_lang }}</a>
                                        </p>
                                        {% if session.services_enable %}
                                            <p>
                                                <a class="btn btn-warning btn-block btn-sm" role="button" data-toggle="popover" id="session-{{ session.id }}-services">{{ 'SeeServices'|get_plugin_lang('BuyCoursesPlugin') }}</a>
                                            </p>
                                        {% endif %}
                                        <p class="buttom-subscribed">
                                            {% if session.is_subscribed %}
                                                {{ already_subscribed_label }}
                                            {% else %}
                                                {{ session.subscribe_button }}
                                            {% endif %}
                                        </p>
                                    {% endif %}
                                </div>
                            </div>

                            {% if _u.logged %}
                                <script>
                                    $('#session-{{ session.id }}-sequences').popover({
                                        placement: 'bottom',
                                        html: true,
                                        trigger: 'click',
                                        content: function () {
                                            var content = '';

                                            {% if session.sequences %}
                                                {% for sequence in session.sequences %}
                                                    content += '<p class="lead">{{ sequence.name }}</p>';

                                                    {% if sequence.requirements %}
                                                        content += '<p><em class="fa fa-sort-amount-desc"></em> {{ 'RequiredSessions'|get_lang }}</p>';
                                                        content += '<ul>';

                                                        {% for requirement in sequence.requirements %}
                                                            content += '<li>';
                                                            content += '<a href="{{ _p.web ~ 'session/' ~ requirement.id ~ '/about/' }}">{{ requirement.name }}</a>';
                                                            content += '</li>';
                                                        {% endfor %}

                                                        content += '</ul>';
                                                    {% endif %}

                                                    {% if sequence.dependencies %}
                                                        content += '<p><em class="fa fa-sort-amount-desc"></em> {{ 'DependentSessions'|get_lang }}</p>';
                                                        content += '<ul>';

                                                        {% for dependency in sequence.dependencies %}
                                                            content += '<li>';
                                                            content += '<a href="{{ _p.web ~ 'session/' ~ dependency.id ~ '/about/' }}">{{ dependency.name }}</a>';
                                                            content += '</li>';
                                                        {% endfor %}

                                                        content += '</ul>';
                                                    {% endif %}

                                                    {% if session.sequences|length > 1 %}
                                                        content += '<hr>';
                                                    {% endif %}
                                                {% endfor %}
                                            {% else %}
                                                content = "{{ 'NoDependencies'|get_lang }}";
                                            {% endif %}

                                            return content;
                                        }
                                    });
                                </script>
                                <script>
                                    $('#session-{{ session.id }}-services').popover({
                                        placement: 'bottom',
                                        html: true,
                                        trigger: 'click',
                                        content: function () {
                                            var content = '';

                                            {% if session.services %}
                                                content += '<ul>';
                                                {% for service in session.services %}
                                                    content += '<li>';
                                                    content += '{{ service.service.name }}';
                                                    content += '</li>';
                                                {% endfor %}
                                                content += '</ul>';
                                            {% else %}
                                                content = "{{ 'NoServices'|get_plugin_lang('BuyCoursesPlugin') }}";
                                            {% endif %}

                                            return content;
                                        }
                                    });
                                </script>
                            {% endif %}
                        </div>
                    </div>
                {% else %}
                    <div class="col-xs-12">
                        <div class="alert alert-warning">
                            {{ 'NoResults'|get_lang }}
                        </div>
                    </div>
                {% endfor %}
            </div>
        </div>
    </section>

    {{ catalog_pagination }}

{% endblock %}
