#!/usr/bin/env ruby
# muzis.rb
# only POST method
# https://github.com/muzis-git/muzis-open-api

require 'rubygems'
#require 'uri'
#require 'net/http'
require 'colored'
require 'json'
require 'faraday'
require 'faraday_middleware'

URL = 'http://muzis.ru'
# 1) Поиск по трекам и исполнителям по названию трека или исполнителя, тексту трека, значению.
PATH_SEARCH = '/api/search.api'
# 2) Создание релевантного списка треков по другому треку или исполнителю
PATH_OBJ = '/api/stream_from_obj.api'
# 3) Создание релевантного списка треков по списку слов (по тексту песен)
PATH_LYRICS = '/api/stream_from_lyrics.api'
# 4) Создание релевантного списка треков по значению или значениям.
PATH = '/api/stream_from_values.api'
# 5) Получение списка похожих исполнителей
PATH_SIMILAR = '/api/similar_performers.api'




# url = 'https://api.spotify.com/v1'
data = {'performer' => 'Beatles'}

conn = Faraday.new(url: URL) do |faraday|
  faraday.request  :url_encoded
#  faraday.request :json
  faraday.adapter Faraday.default_adapter
#  faraday.response :logger
  faraday.response :json
  faraday.headers['User-Agent'] = 'muzis-agent Danilov'
  faraday.headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8'
#  faraday.proxy "#{proxy}"
end
puts "PATH_LYRICS new conn".red
aa = conn.post(PATH_LYRICS, {"lyrics": "снег"}).body
aa['songs'].each do |a|
  p a
  puts "#{a['performer']}".green
#  puts "a".red
end

#response = conn.post('performer' => 'Beatles')
#p body
puts "end".red
#p conn
# puts "PATH_SEARCH new".red
# conn.post do |req|
#   req.url PATH_SEARCH
#   req.headers['Content-Type'] = 'application/json'
# #  req.headers['User-Agent'] = 'muzis-agent Danilov'
#   req.body = '{"q_performer": "rammstein"}'
# end
puts "end".blue
#res = conn.post(PATH_SEARCH, {"q_performer": "rammstein"}).body
def similar_by(parameter, name)
  res = conn.post(PATH_SEARCH, {"q_performer": name}).body
  id = res['performers'][0]['id']

end
#puts similar_by("q_performer", "Стас Михайлов")
puts "=====---".red
res = conn.post(PATH_SEARCH, {"q_performer": "Стас Михайлов"}).body
id = res['performers'][0]['id']
#puts id =  res.performers.id
res = conn.post(PATH_SIMILAR, {"performer_id": id}).body
#p info = JSON.parse(res)
p res
#p conn.response.dump_body(body)
#puts site = Faraday.new.post(URL).body
# ===========================


