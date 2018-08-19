<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-3">
                <div class="card card-default">
                    <div class="card-header">
                        プライベートチャット
                    </div>

                    <ul class="list-group">
                        <li class="list-group-item"
                        @click.prevent="openChat(friend)"
                        v-for="friend in friends"
                        :key="friend.id">
                            <a href="">{{friend.name}}</a>
                            <i class="fa fa-circle float-right text-success" 
                            v-if="friend.online"
                            aria-hidden="true"></i>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-md-9">
                <span v-for="friend in friends" :key="friend.id" v-if="friend.session">
                    <message-component 
                    v-if="friend.session.open"
                    @close="close(friend)"
                    :friend=friend
                    ></message-component>
                </span>
            </div>
        </div>
    </div>
</template>

<script>
    import MessageComponent from './MessageComponent';

    export default {
        components: { MessageComponent },
        data() {
            return {
                friends: []
            }
        },
        methods: {
            close(friend) {
                friend.session.open = false;
            },
            getFriends() {
                axios.get('/getFriends').then(res => this.friends = res.data.data);
                // axios.get('/getFriends').then(res => console.log(res));
            },
            openChat(friend) {
                // まず、他の開いてるセッションを閉じよう
                this.friends.forEach(friend => {
                    if (friend.session) friend.session.open = false;
                });

                if (friend.session) {    
                    friend.session.open = true;
                } else {
                    // セッションを作成
                    this.createSession(friend);
                }
            },
            createSession(friend) {
              axios.post('/session/create', {friend_id: friend.id})
                  .then(res => {
                      friend.session = res.data.data;
                      friend.session.open = true;
                  });
            }
        },
        created() {
            this.getFriends();

            Echo.channel('Chat').listen('SessionEvent', e => {
                // 相手の方がセッションを作成したら、そのセッション情報をfriend.sessionに格納する
                let friend = this.friends.find(friend => friend.id == e.session_by);
                friend.session = e.session;
            });

            // https://laravel.com/docs/5.6/broadcasting#presence-channels
            Echo.join('Chat')
            .here((users) => {
                this.friends.forEach(friend => {
                    users.forEach(user => friend.online = (user.id == friend.id)? true : false)
                })
            })
            .joining((user) => {
                this.friends.forEach(friend => {
                    if (user.id == friend.id) friend.online = true;
                })
            })
            .leaving((user) => {
                this.friends.forEach(friend => {
                    if (user.id == friend.id) friend.online = false;
                })
            });
        }
    }
</script>
