/**
 * jQuery 4 compatibility shims for QueryBuilder.
 * QueryBuilder still calls helpers that were removed in jQuery 4.
 * @see https://querybuilder.js.org/
 */

if (!$.isArray) $.isArray = Array.isArray;
if (!$.isFunction) $.isFunction = (value) => typeof value === 'function';
if (!$.trim) $.trim = (value) => (value == null ? '' : String(value).trim());
