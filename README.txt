Available variables for the registered user in the templates are:
{group_id}
{username}
{screen_name}
{email}
{url}
{location}
{ip_address}
{join_date}
{language}
{time_format}
{timezone}
{daylight_savings}
{group_title}
{group_description}
{member_id}

In addition, custom fields are parsed as well. As an example, if you have a custom member field called "company" set to display on the registration form, the following would also be available:
{company} - The value submitted from the user
{company_label}
{company_name}
{company_description}

These variables are all replaced into the template before template parsing.
