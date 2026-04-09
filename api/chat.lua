function load_json()
   if json_c then return end
   local get_api = gg.makeRequest("https://chat.weid.top/api/json.lua")
   if get_api.code == 200 then
      json_c = load(get_api.content)()      
   else
      gg.alert("load the json api failed code: " .. get_api.code)
   end
end

function send_chat(room_id, username, message)
   local room = room_id or "TEST"
   local user = username or "unknown"   
   if not message or message == "" then return end
   local post = "https://chat.weid.top/send_chat.php"
   local data = "room_id=" .. room .. "&username=" .. user .. "&message=" .. message
   local res = gg.makeRequest(post, nil, data)
   load_json()
   local json_post = json_c["decode"](res.content)    
   if json_post.success then      
      get_chat(room)
   else
      gg.alert("send failed: " .. json_post.error)
   end
end

function get_chat(room_id, username)
   local room = room_id or "TEST"
   local user = username or "unknown"
   local get = "https://chat.weid.top/get_chat.php?room_id=" .. room
   local res = gg.makeRequest(get) 
   load_json()   
   local json_get = json_c["decode"](res.content)
   if json_get.success then
      local mess = ""   
      for i, msg in ipairs(json_get.messages) do
         mess = mess .. "[" .. msg.timestamp .. "] " .. msg.username .. ":\n" .. msg.message .. "\n\n"
      end
      while true do
         local view = gg.alert(mess,"close","refesh","join chat")
         if not view or view==1 then break
         elseif view==3 then
            local ip = gg.prompt({"[CHAT INPUT] -> " .. user},{},{"text"})
            if ip then
               send_chat(room, user, ip[1])
            break end
         elseif view==2 then
            get_chat(room, user)
         break end
      end
   end
end