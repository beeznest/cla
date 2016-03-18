<link rel="stylesheet" type="text/css" href="../resources/css/style.css"/>

<div id="buy-courses-tabs">
    {% if not show_services_only %}
        <ul class="nav nav-tabs buy-courses-tabs" role="tablist">
            <li id="buy-courses-tab" class="" role="presentation">
                <a href="course_panel.php" aria-controls="buy-courses" role="tab">{{ 'MyCourses'| get_lang }}</a>
            </li>
            {% if sessions_are_included %}
                <li id="buy-sessions-tab" class="" role="presentation">
                    <a href="session_panel.php" aria-controls="buy-sessions" role="tab">{{ 'MySessions'| get_lang }}</a>
                </li>
            {% endif %}
            {% if services_are_included %}
                <li id="buy-services-tab" class="active" role="presentation">
                    <a href="service_panel.php" aria-controls="buy-services" role="tab">{{ 'MyServices'| get_plugin_lang('BuyCoursesPlugin') }}</a>
                </li>
            {% endif %}
            <li id="buy-courses-tab" class="" role="presentation">
                <a href="payout_panel.php" aria-controls="buy-courses" role="tab">{{ 'MyPayouts'| get_plugin_lang('BuyCoursesPlugin') }}</a>
            </li>
        </ul>
    {% endif %}
        
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>{{ 'Service'| get_plugin_lang('BuyCoursesPlugin')  }}</th>
                <th class="text-center">{{ 'ServiceType'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th class="text-center">{{ 'PaymentMethod'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th class="text-center">{{ 'Price'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th class="text-center">{{ 'OrderDate'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th class="text-center">{{ 'Expire'|get_lang }}</th>
                <th class="text-center">{{ 'OrderReference'|get_plugin_lang('BuyCoursesPlugin') }}</th>
                <th class="text-center">{{ 'AutoBilling'|get_plugin_lang('BuyCoursesPlugin') }}</th>
            </tr>
        </thead>
        <tbody>
            {% for sale in sale_list %}
                <tr class="{{ sale.status == service_sale_statuses.status_cancelled ? 'buy-courses-cross-out' : '' }}">
                    {% if sale.applies_to == 4 %}
                        <td><a href="{{ _p.web_plugin ~ 'buycourses/src/package_panel.php?' ~ { 'id': sale.id } | url_encode() }}">{{ sale.name }}</a></td>
                    {% else %}
                        <td>{{ sale.name }}</td>
                    {% endif %}
                    <td class="text-center">{{ sale.service_type }}</td>
                    <td class="text-center">{{ sale.payment_type }}</td>
                    <td class="text-right">{{ sale.currency ~ ' ' ~ sale.price }}</td>
                    <td class="text-center">{{ sale.date }}</td>
                    <td class="text-center">{{ sale.date_end }}</td>
                    <td class="text-center">{{ sale.reference }}</td>
                    <td class="text-center">
                    {% if sale.status == service_sale_statuses.status_completed %}
                        {% if sale.recurring_payment == 1 %}
                            <a href="{{ _p.web_plugin ~ 'buycourses/src/recurring_payment_process.php?' ~ { 'order': sale.id, 'action': 'disable_recurring_payment'}|url_encode() }}" class="btn btn-danger btn-sm">
                                <em class="fa fa-paypal fa-fw"></em> {{ 'Disable'|get_lang }}
                            </a>
                        {% else %}
                            <a href="{{ _p.web_plugin ~ 'buycourses/src/recurring_payment_process.php?' ~ { 'order': sale.id, 'action': 'enable_recurring_payment'}|url_encode() }}" class="btn btn-success btn-sm">
                                <em class="fa fa-paypal fa-fw"></em> {{ 'Enable'|get_lang }}
                            </a>
                        {% endif %}
                    {% elseif sale.status == service_sale_statuses.status_pending %}
                        
                        {{ 'Pending'|get_lang }}
                        
                    {% elseif sale.status == service_sale_statuses.status_cancelled %}
                        
                        {{ 'Cancelled'|get_lang }}
                        
                    {% endif %}
                    </td>
                </tr>
            {% endfor %}
        </tbody>
    </table>

  
</div>
