<template>
    <div class="card card-default chat-box">
        <div class="card-header">
            <b :class="{'text-danger': session.block}">
                {{ friend.name }} <span v-if="isTyping">さんが入力中です</span>
                <span v-if="session.block">(ブロック中)</span>
            </b>

            <!-- 閉じる -->
            <a href="" @click.prevent="close">
                <i class="fa fa-times float-right" aria-hidden="true"></i>
            </a><!-- 閉じる -->

            <!-- オプション -->
            <div class="dropdown float-right mr-4">
                <!-- <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Dropdown button
                </button> -->
                <a href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-ellipsis-v px-4" aria-hidden="true"></i>
                </a>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" href="#" v-if="session.block && can"
                    @click.prevent="unblock">ブロック解除</a>
                    <a class="dropdown-item" href="#" @click.prevent="block" v-if="!session.block">ブロック</a>
                    <a class="dropdown-item" href="#" @click.prevent="clear">チャットをクリア</a>
                </div>
            </div>

        </div>
        <div class="card-body" v-chat-scroll>
            <p class="card-text"
               v-for="chat in chats"
               :key="chat.id"
               :class="{'text-right':chat.type == 0, 'text-success':chat.read_at!=null}">
                {{ chat.message }}
                <br>
                <span style="font-size:8px;">{{chat.read_at}}</span>
            </p>
        </div>

        <form class="card-footer" @submit.prevent="send">
            <div class="form-group">
                <input type="text" class="form-control" placeholder="メッセージをかいて"
                :disabled="session.block" v-model="message">
            </div>
        </form>
    </div>
</template>

<script>
export default {
    props: ['friend'],
    data() {
        return {
            chats: [],
            message: null,
            isTyping: false
        }
    },
    computed: {
        session() {
          return this.friend.session;
        },
        can() {
          return this.session.blocked_by == auth.id;
        }
    },
    watch: {
        message(value) {
            if (value) {// https://laravel.com/docs/5.6/broadcasting#client-events
                // pusherの方で'App setting'->'enable client events'を有効化しないとエラーになります
                Echo.private(`Chat.${this.friend.session.id}`)
                    .whisper('typing', {
                        name: auth.name
                    });
            }
        }
    },
    methods: {
        send() {
            if (this.message) {
                this.pushToChats(this.message);
                axios.post(`/send/${this.friend.session.id}`, {
                  content: this.message,
                  to_user: this.friend.id
                })// レスポンスのデータと交換することで'read_at'を得る
                .then(res => this.chats[this.chats.length - 1].id = res.data)

                this.message = null;
            }
        },
        pushToChats(message) {
            this.chats.push({ message: message, type: 0, read_at:null, send_at: 'たった今'});
        },
        close() {
            this.$emit('close');
        },
        clear() {
            axios.post(`/session/${this.friend.session.id}/clear`).then(res => this.chats = []);
        },
        block() {
            this.session.block = true;
            axios.post(`/session/${this.friend.session.id}/block`)
                .then(res => this.session.blocked_by = auth.id) // auth はapp.blade.phpの上の方に書いてある(あんまりよくないよね)
        },
        unblock() {
            this.session.block = false;
            axios.post(`/session/${this.friend.session.id}/unblock`)
                .then(res => this.session.blocked_by = null)
        },
        getAllMessages() {
          axios.get(`/session/${this.friend.session.id}/chats`)
              .then(res => this.chats = res.data.data)
        },
        read() {
            axios.get(`/session/${this.friend.session.id}/read`);
        }
    },
    created() {
        this.read();
        this.getAllMessages();

        Echo.private(`Chat.${this.friend.session.id}`).listen('PrivateChatEvent', e => {
            if (this.friend.session.open) this.read(); // ?????
            this.chats.push({message: e.content, type: 1, send_at: "たった今"})
        });

        Echo.private(`Chat.${this.friend.session.id}`).listen('MessageReadEvent', e => {
            this.chats.forEach(chat => chat.id == e.chat.id ? chat.read_at = e.chat.read_at : '')
        });

        Echo.private(`Chat.${this.friend.session.id}`).listen('BlockEvent', e => {
            this.session.block = e.blocked;
        });

        // https://laravel.com/docs/5.6/broadcasting#client-events
        Echo.private(`Chat.${this.friend.session.id}`)
            .listenForWhisper('typing', (e) => {
                this.isTyping = true;
                setTimeout(() => {
                    this.isTyping = false;
                }, 2000);
            });
    }
}
</script>

<style>
.chat-box {
    height: 400px;
}
.card-body {
    overflow-y: scroll;
}
</style>

