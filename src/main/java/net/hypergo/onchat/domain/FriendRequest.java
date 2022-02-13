package net.hypergo.onchat.domain;

import com.fasterxml.jackson.annotation.JsonIdentityInfo;
import com.fasterxml.jackson.annotation.ObjectIdGenerators;
import net.hypergo.onchat.enumerate.RequestStatus;
import org.hibernate.annotations.ColumnDefault;
import org.hibernate.annotations.DynamicUpdate;

import javax.persistence.*;
import javax.validation.constraints.Max;
import javax.validation.constraints.Size;
import java.util.StringJoiner;

@Table
@Entity
@DynamicUpdate
@JsonIdentityInfo(generator = ObjectIdGenerators.PropertyGenerator.class, property = "id")
public class FriendRequest extends IdEntity {
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "requester_id", nullable = false)
    private User requester;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "target_id", nullable = false)
    private User target;

    @Max(50)
    @Column(length = 100)
    private String requestReason;

    @Max(50)
    @Column(length = 100)
    private String rejectReason;

    @Column(nullable = false, columnDefinition = "TINYINT(1) UNSIGNED")
    @ColumnDefault("0")
    @Enumerated(EnumType.ORDINAL)
    private RequestStatus status = RequestStatus.WAIT;

    @Column(nullable = false, columnDefinition = "BOOLEAN")
    @ColumnDefault("TRUE")
    private Boolean requesterReaded = true;

    @Column(nullable = false, columnDefinition = "BOOLEAN")
    @ColumnDefault("FALSE")
    private Boolean targetReaded = false;

    @Size(min = 1, max = 15)
    @Column(length = 30)
    private String requesterAlias;

    @Size(min = 1, max = 15)
    @Column(length = 30)
    private String targetAlias;

    public User getRequester() {
        return requester;
    }

    public void setRequester(User requester) {
        this.requester = requester;
    }

    public User getTarget() {
        return target;
    }

    public void setTarget(User target) {
        this.target = target;
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

    public RequestStatus getStatus() {
        return status;
    }

    public void setStatus(RequestStatus status) {
        this.status = status;
    }

    public Boolean getRequesterReaded() {
        return requesterReaded;
    }

    public void setRequesterReaded(Boolean requesterReaded) {
        this.requesterReaded = requesterReaded;
    }

    public Boolean getTargetReaded() {
        return targetReaded;
    }

    public void setTargetReaded(Boolean targetReaded) {
        this.targetReaded = targetReaded;
    }

    public String getRequesterAlias() {
        return requesterAlias;
    }

    public void setRequesterAlias(String requesterAlias) {
        this.requesterAlias = requesterAlias;
    }

    public String getTargetAlias() {
        return targetAlias;
    }

    public void setTargetAlias(String targetAlias) {
        this.targetAlias = targetAlias;
    }

    @Override
    public String toString() {
        return new StringJoiner(", ", FriendRequest.class.getSimpleName() + "[", "]")
                .add("id=" + id)
                .add("requester=" + requester.getId())
                .add("target=" + target.getId())
                .add("requestReason='" + requestReason + "'")
                .add("rejectReason='" + rejectReason + "'")
                .add("status=" + status)
                .add("requesterReaded=" + requesterReaded)
                .add("targetReaded=" + targetReaded)
                .add("requesterAlias='" + requesterAlias + "'")
                .add("targetAlias='" + targetAlias + "'")
                .toString();
    }
}
