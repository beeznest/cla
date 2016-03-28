<link rel="stylesheet" type="text/css" href="{{ _p.web_plugin ~ 'buycourses/resources/css/style.css' }}"/>

<div id="service-information">
    
    <div class="row">
        <div class="col-xs-12">
            <h3 class="text-uppercase buy-courses-title-color">{{ service.name }}</h3>
        </div>

        {% if service.video_url %}
            <div class="col-sm-6 col-md-7 col-xs-12">
                <div class="embed-responsive embed-responsive-16by9">
                    {{ essence.replace(service.video_url) }}
                </div>
            </div>
        {% endif %}

        <div class="{{ service.video_url ? 'col-sm-6 col-md-5 col-xs-12' : 'col-sm-12 col-xs-12' }}">
            <div class="block">
                <div class="buy-courses-description-service">
                    {{ service.description }}
                </div>
                <div class="row pull-right">
                    <div class="col-md-4 col-md-offset-4">
                        {% if not _u.logged %}
                            <a href="{{ _p.web_main ~ 'auth/inscription.php?from=service&id=' ~ service.id }}" class="btn btn-info btn-lg">
                                <em class="fa fa-sign-in fa-fw"></em> {{ 'SignUp'|get_lang }}
                            </a>
                        {% else %}
                            <a href="{{ _p.web_plugin ~ 'buycourses/src/service_process.php?t=4&i=' ~ service.id }}" class="btn btn-danger btn-lg">
                                <em class="fa fa-sign-in fa-fw"></em> {{ 'Subscribe'|get_lang }}
                            </a>
                        {% endif %}
                    </div>
                </div>
            </div>
        </div>
    </div>
    </br>
    </br>
    <div class="row info-course">
        <div class="col-xs-12 col-md-7">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4>{{ 'ServiceInformation'|get_plugin_lang('BuyCoursesPlugin') }}</h4>
                </div>
                <div class="panel-body">
                    {{ service.service_information }}
                </div>
            </div>
        </div>

        <div class="col-xs-12 col-md-5">
            <div class="panel panel-default social-share">
                <div class="panel-heading">{{ "ShareWithYourFriends"|get_lang }}</div>
                <div class="panel-body">
                    <div class="icons-social text-center">
                        <a href="https://www.facebook.com/sharer/sharer.php?{{ {'u': pageUrl}|url_encode }}" target="_blank" class="btn bnt-link btn-lg">
                            <em class="fa fa-facebook fa-2x"></em>
                        </a>
                        <a href="https://twitter.com/home?{{ {'status': session.getName() ~ ' ' ~ pageUrl}|url_encode }}" target="_blank" class="btn bnt-link btn-lg">
                            <em class="fa fa-twitter fa-2x"></em>
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?{{ {'mini': 'true', 'url': pageUrl, 'title': session.getName() }|url_encode }}" target="_blank" class="btn bnt-link btn-lg">
                            <em class="fa fa-linkedin fa-2x"></em>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
