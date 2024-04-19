/* Re-usable error handler for UI components */

define(["dijit/registry", "aps/Message", "aps/PageContainer"], function (
  registry,
  Message,
  PageContainer
) {
  return function (err, type) {
    // console.log("In error handler routine");
    var errData = JSON.parse(err.response.text);
    aps.apsc.cancelProcessing();
    var page = registry.byId("page");
    if (!page) {
      page = new PageContainer({ id: "page" });
      page.placeAt(document.body, "first");
    }
    var messages = page.get("messageList");
    /* Remove all current messages from the screen */
    messages.removeAll();
    /* And display the new message */
    messages.addChild(
      new Message({
        description: "<br />" + errData.message,
        type: type || "error",
      })
    );
  };
});
