  function updateSelections(maxPrice, maxPicks, saved)
  {
      var pickTable = document.getElementById("picksTable");
      var pickChoices = document.getElementsByName("pick[]");

      var selCount = 0;
      var totalPrice = 0;
      for(var i = 0; i < pickChoices.length; i++)
      {
          var choice = pickChoices[i];
          if(choice.checked)
          {
              selCount++;
              totalPrice += Number(pickTable.tBodies[0].rows[i].cells[2].innerHTML.substring(1));
          }
      }

      if(selCount > 0)
      {
          var text  = "";
          var choice;

          text += "<div class=\"table-default\" style=\"margin: 0 0 0 5px; \">";
          text += "<dl class=\"detail\" style=\"margin-top: 0; margin-bottom: 0; \">";
          text += "<dt>Total Spent</dt>";
          text += "<dd id=\"selPrice\">";
          text += "$" + totalPrice;
          text += "</dd>";
          text += "<dt>Balance</dt>";
          text += "<dd id=\"balance\">";
          if(totalPrice > maxPrice)
          {
            text += "($" + (totalPrice - maxPrice) + ")";
          }
          else
          {
            text += "$" + (maxPrice - totalPrice);
          }
          text += "</dd>";
          text += "<dt>Number of Picks</dt>";
          text += "<dd id=\"selPicks\">";
          text += selCount;
          text += "</dd>";
          text += "</dl>";

          text += "<table class=\"fantasy-results\" id=\"selectionsTable\" style=\"width: 300px; \">";
          text += "<tr>";
          text += "<th colspan=\"2\">Driver</th><th width=\"65\">Price</th>";
          text += "</tr>";
          for(var i = 0; i < pickChoices.length; i++)
          {
              choice = pickChoices[i];
              if(choice.checked)
              {
                  text += "<tr>";

                  text += "<td width=\"10\">&nbsp;</td>";

                  text += "<td style=\"text-align: right; \">";
                  text += pickTable.tBodies[0].rows[i].cells[1].getElementsByTagName('label')[0].innerHTML;
                  text += "</td>";

                  text += "<td style=\"background: #dddddd; \">";
                  text += pickTable.tBodies[0].rows[i].cells[2].innerHTML;
                  text += "</td>";

                  text += "</tr>";
              }
          }

          text += "</table>";
          text += "</div>";

          document.getElementById("selections").innerHTML = text;

          var selTable = document.getElementById("selectionsTable");
          var j = 1;
          for(var i = 0; i < pickChoices.length; i++)
          {
              choice = pickChoices[i];
              if(choice.checked)
              {
                  selTable.tBodies[0].rows[j].cells[1].style.color
                    = pickTable.tBodies[0].rows[i].cells[1].style.color;
                  selTable.tBodies[0].rows[j].cells[1].style.background
                    = pickTable.tBodies[0].rows[i].cells[1].style.background;
                  selTable.tBodies[0].rows[j].cells[0].style.background
                    = pickTable.tBodies[0].rows[i].cells[0].style.background;

                  j++;
              }
          }


          if(totalPrice > maxPrice)
          {
              document.getElementById("selPrice").style.color = "#cc0000";
          }

          if(selCount > maxPicks)
          {
              document.getElementById("selPicks").style.color = "#cc0000";
          }
      }
      else if(document.getElementById("selections") != null)
      {
          document.getElementById("selections").innerHTML = "";
      }

      return true;
  }
