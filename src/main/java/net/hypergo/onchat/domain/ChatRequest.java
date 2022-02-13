package net.hypergo.onchat.domain;

import com.fasterxml.jackson.annotation.JsonIdentityInfo;
import com.fasterxml.jackson.annotation.ObjectIdGenerators;
import net.hypergo.onchat.enumerate.RequestStatus;
import org.hibernate.annotations.ColumnDefault;
import org.hibernate.annotations.DynamicUpdate;
import org.hibernate.annotations.Type;

import javax.persistence.*;
import javax.validation.constraints.Max;
import java.util.Set;
import java.util.StringJoiner;

@Table
@Entity
@DynamicUpdate
@JsonIdentityInfo(generator = ObjectIdGenerators.PropertyGenerator.class, property = "id")
public class ChatRequest extends IdEntity {
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "chatroom_id", nullable = false)
    private Chatroom chatroom;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "requester_id", nullable = false)
    private User requester;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "handler_id")
    private User handler;

    @Column(nullable = false, columnDefinition = "TINYINT(1) UNSIGNED")
    @ColumnDefault("0")
    @Enumerated(EnumType.ORDINAL)
    private RequestStatus status = RequestStatus.WAIT;

    @Max(50)
    @Column(length = 100)
    private String requestReason;

    @Max(50)
    @Column(length = 100)
    private String rejectReason;

    @Type(type = "JSON")
    @Column(nullable = false, columnDefinition = "JSON")
    private Set<Long> readedList;

    public Chatroom getChatroom() {
        return chatroom;
    }

    public void setChatroom(Chatroom chatroom) {
        this.chatroom = chatroom;
    }

    public User getRequester() {
        return requester;
    }

    public void setRequester(User requester) {
        this.requester = requester;
    }

    public User getHandler() {
        return handler;
    }

    public void setHandler(User handler) {
        this.handler = handler;
    }

    public RequestStatus getStatus() {
        return status;
    }

    public void setStatus(RequestStatus status) {
        this.status = status;
    }

    public String getRequestReason() {
        return requestReason;
    }

    public void setRequestReason(String requestReason) {
        this.requestReason = requestReason;
    }

    public String getRejectReason() {
        return rejectReason;
    }

    public void setRejectReason(String rejectReason) {
        this.rejectReason = rejectReason;
    }

    public Set<Long> getReadedList() {
        return readedList;
    }

    public void setReadedList(Set<Long> readedList) {
        this.readedList = readedList;
    }

    @Override
    public String toString() {
        return new StringJoiner(", ", ChatRequest.class.getSimpleName() + "[", "]")
                .add("id=" + id)
                .add("chatroom=" + chatroom.getId())
                .add("requester=" + requester.getId())
                .add("handler=" + handler.getId())
                .add("status=" + status)
                .add("requestReason='" + requestReason + "'")
                .add("rejectReason='" + rejectReason + "'")
                .add("readedList=" + readedList)
                .toString();
    }
}
