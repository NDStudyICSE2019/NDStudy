/*
 * $Id: admin_users.js 14022 2012-02-15 12:53:14Z zefredz $
 */

$(document).ready(function(){
    // Fetch courses for a given user, and display it inside a qtip
    $("a.showUserCourses").each(function()
    {
        $(this).qtip({
            content: {
                url: "./ajax/ajax_requests.php",
                data: { action: "getUserCourseList", userId: $(this).find("span").attr("class") },
                method: "get"
            },
            
            show: "mouseover",
            hide: "mouseout",
            position: {
                corner: {
                    target: "topRight",
                    tooltip: "bottomRight"
                }
            },
            
            style: {
                width: 200,
                padding: 5,
                background: "#CCDDEE",
                color: "black",
                fontSize: "1em",
                textAlign: "center",
                border: {
                    width: 7,
                    radius: 5,
                    color: "#CCDDEE"
                }
            }
        });
    });
    
    // Fetch classes for a given user, and display it inside a qtip
    $("span.showUserClasses").each(function()
    {
        $(this).qtip({
            content: {
                url: "./ajax/ajax_requests.php",
                data: { action: "getUserClassList", userId: $(this).find("span").attr("class") },
                method: "get"
            },
            
            show: "mouseover",
            hide: "mouseout",
            position: {
                corner: {
                    target: "topRight",
                    tooltip: "bottomRight"
                }
            },
            
            style: {
                width: 200,
                padding: 5,
                background: "#CCDDEE",
                color: "black",
                fontSize: "1em",
                textAlign: "center",
                border: {
                    width: 7,
                    radius: 5,
                    color: "#CCDDEE"
                }
            }
        });
    });
    
    // Fetch categories for a given user, and display it inside a qtip
    $("a.showUserCategory").each(function()
    {
        $(this).qtip({
            content: {
                url: "./ajax/ajax_requests.php",
                data: { action: "getUserCategoryList", userId: $(this).find("span").attr("class") },
                method: "get"
            },
            
            show: "mouseover",
            hide: "mouseout",
            position: {
                corner: {
                    target: "topRight",
                    tooltip: "bottomRight"
                }
            },
            
            style: {
                width: 200,
                padding: 5,
                background: "#CCDDEE",
                color: "black",
                fontSize: "1em",
                textAlign: "center",
                border: {
                    width: 7,
                    radius: 5,
                    color: "#CCDDEE"
                }
            }
        });
    });
});